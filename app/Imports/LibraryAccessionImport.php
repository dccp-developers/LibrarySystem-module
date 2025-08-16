<?php

declare(strict_types=1);

namespace Modules\Library\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Modules\Library\Models\Book;
use Modules\Library\Models\Author;
use Modules\Library\Models\Category;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class LibraryAccessionImport implements ToModel, WithStartRow, WithBatchInserts, WithChunkReading
{
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];

    public function model(array $row)
    {
        try {
            // Map Excel columns by position (0-based index)
            // A=0, B=1, C=2, D=3, E=4, F=5, G=6, H=7, I=8, J=9, K=10, L=11, M=12, N=13, O=14, P=15
            $accessionNumber = $this->cleanString($row[2] ?? ''); // Column C - Accession Number
            $callNumber = $this->cleanString($row[3] ?? ''); // Column D - Call number  
            $authorName = $this->cleanString($row[4] ?? ''); // Column E - Author
            $editor = $this->cleanString($row[5] ?? ''); // Column F - Editor
            $title = $this->cleanString($row[6] ?? ''); // Column G - Title of the Book
            $edition = $this->cleanString($row[7] ?? ''); // Column H - Edition
            $volumes = $this->cleanString($row[8] ?? ''); // Column I - Volumes
            $pages = $this->extractPages($row[9] ?? ''); // Column J - Pages
            $sourceOfFund = $this->cleanString($row[10] ?? ''); // Column K - Source of Fund / Donor
            $costPrice = $this->extractPrice($row[11] ?? ''); // Column L - Cost Price
            $publisher = $this->cleanString($row[12] ?? ''); // Column M - Publisher
            $year = $this->extractYear($row[13] ?? ''); // Column N - Year
            $location = $this->cleanString($row[14] ?? ''); // Column O - Shelf Location
            $notes = $this->cleanString($row[15] ?? ''); // Column P - Notes/Remarks/Subject

            // Skip empty rows
            if (empty($title) && empty($authorName)) {
                $this->skippedCount++;
                return null;
            }

            // Enhanced author extraction logic
            $authorName = $this->extractAuthorName($authorName, $editor, $title);
            
            // Default to 'Unknown Author' if still empty after all extraction attempts
            if (empty($authorName)) {
                $authorName = 'Unknown Author';
            }

            if (empty($title)) {
                $this->skippedCount++;
                return null;
            }

            // Find or create author
            $author = Author::firstOrCreate(
                ['name' => $authorName],
                ['biography' => null, 'birth_date' => null, 'nationality' => null]
            );

            // Determine category based on call number or subject
            $category = $this->determineCategory($callNumber, $notes);

            // Create book record
            $book = new Book([
                'title' => $title,
                'isbn' => null, // Not available in this dataset
                'author_id' => $author->id,
                'category_id' => $category->id,
                'publisher' => $publisher,
                'publication_year' => $year,
                'pages' => $pages,
                'description' => $notes,
                'total_copies' => 1,
                'available_copies' => 1,
                'location' => $location,
                'status' => 'available',
            ]);

            // Add custom metadata as JSON in description
            $metadata = [
                'accession_number' => $accessionNumber,
                'call_number' => $callNumber,
                'edition' => $edition,
                'volumes' => $volumes,
                'cost_price' => $costPrice,
                'source_of_fund' => $sourceOfFund,
                'editor' => $editor,
                'imported_at' => now()->toDateTimeString(),
            ];

            $book->description = $notes . "\n\n" . "Import Data: " . json_encode($metadata, JSON_PRETTY_PRINT);

            $this->importedCount++;
            return $book;

        } catch (\Exception $e) {
            Log::error('Library import error', [
                'row' => $row,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            $this->errors[] = "Row error: " . $e->getMessage() . " (Title: " . ($title ?? 'Unknown') . ")";
            $this->skippedCount++;
            return null;
        }
    }

    public function startRow(): int
    {
        return 8; // Data starts from row 8 (row 7 is header)
    }

    public function batchSize(): int
    {
        return 50; // Smaller batch size for better memory management
    }

    public function chunkSize(): int
    {
        return 50;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function extractAuthorName(string $authorColumn, string $editorColumn, string $title): string
    {
        // 1. Use author column if not empty
        if (!empty($authorColumn)) {
            return $authorColumn;
        }
        
        // 2. Use editor column if not empty
        if (!empty($editorColumn)) {
            return $editorColumn;
        }
        
        // 3. Extract author from title using improved patterns
        
        // Pattern 1: "edited by AuthorName" - most specific first
        if (preg_match('/edited\s+(?:and\s+translated\s+)?by\s+([^\/\;,\(\)]+?)(?:\s*[\;,\/\(\)]|$)/i', $title, $matches)) {
            $extractedAuthor = $this->cleanAuthorName($matches[1]);
            if ($this->isValidAuthorName($extractedAuthor)) {
                return $extractedAuthor;
            }
        }
        
        // Pattern 2: "by AuthorName" 
        if (preg_match('/\bby\s+([^\/\;,\(\)]+?)(?:\s*[\;,\/\(\)]|$)/i', $title, $matches)) {
            $extractedAuthor = $this->cleanAuthorName($matches[1]);
            if ($this->isValidAuthorName($extractedAuthor)) {
                return $extractedAuthor;
            }
        }
        
        // Pattern 3: "editor, AuthorName" or "editors, AuthorName"
        if (preg_match('/editors?\s*,\s*([^\/\;,\(\)]+?)(?:\s*[\;,\/\(\)]|$)/i', $title, $matches)) {
            $extractedAuthor = $this->cleanAuthorName($matches[1]);
            if ($this->isValidAuthorName($extractedAuthor)) {
                return $extractedAuthor;
            }
        }
        
        // Pattern 4: "/ AuthorName" at the end - more refined
        if (preg_match('/\/\s*([^\/\(\)]+?)$/', $title, $matches)) {
            $extractedAuthor = $this->cleanAuthorName($matches[1]);
            if ($this->isValidAuthorName($extractedAuthor)) {
                return $extractedAuthor;
            }
        }
        
        return ''; // Return empty if no author found
    }

    private function cleanAuthorName(string $author): string
    {
        // Remove common suffixes and prefixes
        $author = preg_replace('/\s*\.\.\.[^\.]*$/', '', $author);
        $author = preg_replace('/\s*et\s+al\.?$/', '', $author);
        $author = preg_replace('/\s*and\s+others?$/', '', $author);
        $author = preg_replace('/\s*etc\.?$/', '', $author);
        
        // Remove leading words that indicate it's not a clean author name
        $author = preg_replace('/^(?:editor|editors|edited|translated|compiled|by)\s+/i', '', $author);
        
        return trim($author);
    }

    private function isValidAuthorName(string $author): bool
    {
        if (empty($author) || strlen($author) < 3) {
            return false;
        }
        
        // Check if it contains too many non-name words
        $commonWords = ['editor', 'editors', 'edited', 'vol', 'volume', 'edition', 'et al', 'translated', 'compilation', 'anthology', 'series'];
        $authorLower = strtolower($author);
        
        foreach ($commonWords as $word) {
            if (str_contains($authorLower, $word)) {
                return false;
            }
        }
        
        // Check if it looks like a proper name (has at least one capital letter and reasonable length)
        return preg_match('/[A-Z]/', $author) && strlen($author) <= 100;
    }

    private function cleanString($value): string
    {
        if ($value === null) {
            return '';
        }
        return trim(preg_replace('/\s+/', ' ', (string) $value));
    }

    private function extractYear($year): ?int
    {
        if (empty($year)) {
            return null;
        }
        
        // Handle Excel date numbers
        if (is_numeric($year) && $year > 25000) {
            try {
                $date = Date::excelToDateTimeObject($year);
                return (int) $date->format('Y');
            } catch (\Exception $e) {
                // Fall back to string processing
            }
        }
        
        $yearStr = (string) $year;
        $matches = [];
        if (preg_match('/(\d{4})/', $yearStr, $matches)) {
            $yearInt = (int) $matches[1];
            return ($yearInt >= 1000 && $yearInt <= date('Y') + 1) ? $yearInt : null;
        }
        
        return null;
    }

    private function extractPages($pages): ?int
    {
        if (empty($pages)) {
            return null;
        }
        
        $matches = [];
        if (preg_match('/(\d+)/', (string) $pages, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
    }

    private function extractPrice($price): ?float
    {
        if (empty($price)) {
            return null;
        }
        
        $matches = [];
        $priceStr = str_replace(',', '', (string) $price);
        if (preg_match('/[\d,]+\.?\d*/', $priceStr, $matches)) {
            return (float) str_replace(',', '', $matches[0]);
        }
        
        return null;
    }

    private function determineCategory(string $callNumber, string $notes): Category
    {
        // Simple categorization based on Dewey Decimal System patterns
        $categoryName = 'General';
        $categoryColor = '#6366f1';

        if (str_contains($callNumber, '000') || str_contains($callNumber, '004')) {
            $categoryName = 'Computer Science & Information';
            $categoryColor = '#10b981';
        } elseif (str_contains($callNumber, '100') || str_contains($callNumber, '1')) {
            $categoryName = 'Philosophy & Psychology';
            $categoryColor = '#8b5cf6';
        } elseif (str_contains($callNumber, '200') || str_contains($callNumber, '2')) {
            $categoryName = 'Religion';
            $categoryColor = '#f59e0b';
        } elseif (str_contains($callNumber, '300') || str_contains($callNumber, '3')) {
            $categoryName = 'Social Sciences';
            $categoryColor = '#ef4444';
        } elseif (str_contains($callNumber, '400') || str_contains($callNumber, '4')) {
            $categoryName = 'Language';
            $categoryColor = '#06b6d4';
        } elseif (str_contains($callNumber, '500') || str_contains($callNumber, '5')) {
            $categoryName = 'Natural Sciences & Mathematics';
            $categoryColor = '#84cc16';
        } elseif (str_contains($callNumber, '600') || str_contains($callNumber, '6')) {
            $categoryName = 'Technology & Applied Sciences';
            $categoryColor = '#f97316';
        } elseif (str_contains($callNumber, '700') || str_contains($callNumber, '7')) {
            $categoryName = 'Arts & Recreation';
            $categoryColor = '#ec4899';
        } elseif (str_contains($callNumber, '800') || str_contains($callNumber, '8')) {
            $categoryName = 'Literature';
            $categoryColor = '#6366f1';
        } elseif (str_contains($callNumber, '900') || str_contains($callNumber, '9')) {
            $categoryName = 'History & Geography';
            $categoryColor = '#8b5a3c';
        }

        return Category::firstOrCreate(
            ['name' => $categoryName],
            [
                'description' => "Auto-generated category from library import",
                'color' => $categoryColor
            ]
        );
    }
}
