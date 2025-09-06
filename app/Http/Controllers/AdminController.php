<?php

namespace App\Http\Controllers;

use App\Models\StudentConfig;
use App\Models\NewConcept;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function setupPage()
    {
        return view('admin.setup');
    }

    public function deletePage()  
    {
        return view('admin.delete');
    }

    public function configIndex()
    {
        $configs = StudentConfig::orderBy('student_first_name')
            ->orderBy('subject')
            ->get();

        return view('admin.config', compact('configs'));
    }

    public function storeConfig(Request $request)
    {
        try {
            $request->validate([
                'student_first_name' => 'required|string|max:255',
                'student_last_name' => 'nullable|string|max:255',
                'subject' => 'required|string|max:255',
                'class_day_1' => 'nullable|string|max:255',
                'class_day_2' => 'nullable|string|max:255',
                'pattern' => 'required|string|max:255',
                'level' => 'required|string|max:255',
                'month' => 'required|string|max:255',
                'year' => 'required|integer|min:2020|max:2030'
            ]);

            StudentConfig::create($request->all());

            return redirect()->route('admin.config')
                ->with('success', 'Student configuration added successfully');

        } catch (\Exception $e) {
            Log::error('Error storing config: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add configuration: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function uploadConfigCsv(Request $request)
    {
        try {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:2048'
            ]);

            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            
            // Get headers from first row
            $headers = array_shift($csvData);
            
            // Clean headers (remove BOM and trim whitespace)
            $headers = array_map(function($header) {
                return trim(str_replace("\xEF\xBB\xBF", '', $header));
            }, $headers);

            $addedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($csvData as $rowIndex => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Create associative array
                    $data = array_combine($headers, $row);
                    
                    // Clean and validate data
                    $cleanData = [
                        'student_first_name' => trim($data['student_first_name'] ?? ''),
                        'student_last_name' => trim($data['student_last_name'] ?? ''),
                        'subject' => trim($data['subject'] ?? ''),
                        'class_day_1' => trim($data['class_day_1'] ?? ''),
                        'class_day_2' => trim($data['class_day_2'] ?? ''),
                        'pattern' => trim($data['pattern'] ?? ''),
                        'level' => trim($data['level'] ?? ''),
                        'month' => trim($data['month'] ?? ''),
                        'year' => (int)($data['year'] ?? date('Y'))
                    ];

                    // Validate required fields
                    if (empty($cleanData['student_first_name']) || empty($cleanData['subject']) || 
                        empty($cleanData['pattern']) || empty($cleanData['level']) || empty($cleanData['month'])) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing required fields";
                        $errorCount++;
                        continue;
                    }

                    // Check if record exists (based on student name, subject, month, year)
                    $existingConfig = StudentConfig::where('student_first_name', $cleanData['student_first_name'])
                        ->where('student_last_name', $cleanData['student_last_name'])
                        ->where('subject', $cleanData['subject'])
                        ->where('month', $cleanData['month'])
                        ->where('year', $cleanData['year'])
                        ->first();

                    if ($existingConfig) {
                        // Update existing record
                        $existingConfig->update($cleanData);
                        $updatedCount++;
                    } else {
                        // Create new record
                        StudentConfig::create($cleanData);
                        $addedCount++;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }

            DB::commit();

            $message = "CSV processed successfully! Added: $addedCount, Updated: $updatedCount";
            if ($errorCount > 0) {
                $message .= ", Errors: $errorCount";
            }

            return redirect()->route('admin.config')
                ->with('success', $message)
                ->with('csv_errors', $errors);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error uploading config CSV: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to upload CSV: ' . $e->getMessage());
        }
    }

    public function updateConfig(Request $request, StudentConfig $config)
    {
        try {
            $request->validate([
                'student_first_name' => 'required|string|max:255',
                'student_last_name' => 'nullable|string|max:255',
                'subject' => 'required|string|max:255',
                'class_day_1' => 'nullable|string|max:255',
                'class_day_2' => 'nullable|string|max:255',
                'pattern' => 'required|string|max:255',
                'level' => 'required|string|max:255',
                'month' => 'required|string|max:255',
                'year' => 'required|integer|min:2020|max:2030'
            ]);

            $config->update($request->all());

            return redirect()->route('admin.config')
                ->with('success', 'Student configuration updated successfully');

        } catch (\Exception $e) {
            Log::error('Error updating config: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update configuration: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroyConfig(StudentConfig $config)
    {
        try {
            $config->delete();

            return redirect()->route('admin.config')
                ->with('success', 'Student configuration deleted successfully');

        } catch (\Exception $e) {
            Log::error('Error deleting config: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete configuration: ' . $e->getMessage());
        }
    }

    public function conceptsIndex()
    {
        $concepts = NewConcept::orderBy('level')
            ->orderBy('subject')
            ->orderBy('worksheet')
            ->get();

        return view('admin.concepts', compact('concepts'));
    }

    public function storeConcept(Request $request)
    {
        try {
            $request->validate([
                'level' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'worksheet' => 'required|integer|min:1',
                'is_new_concept' => 'required|in:Y,N'
            ]);

            NewConcept::create($request->all());

            return redirect()->route('admin.concepts')
                ->with('success', 'New concept added successfully');

        } catch (\Exception $e) {
            Log::error('Error storing concept: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to add concept: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function uploadConceptCsv(Request $request)
    {
        try {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:2048'
            ]);

            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            
            // Get headers from first row
            $headers = array_shift($csvData);
            
            // Clean headers (remove BOM and trim whitespace)
            $headers = array_map(function($header) {
                return trim(str_replace("\xEF\xBB\xBF", '', $header));
            }, $headers);

            $addedCount = 0;
            $updatedCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($csvData as $rowIndex => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Create associative array
                    $data = array_combine($headers, $row);
                    
                    // Clean and validate data
                    $cleanData = [
                        'level' => trim($data['level'] ?? ''),
                        'subject' => trim($data['subject'] ?? ''),
                        'worksheet' => trim($data['worksheet'] ?? ''),
                        'is_new_concept' => strtoupper(isset($data['is_new_concept']) && trim($data['is_new_concept']) !== '' ? trim($data['is_new_concept']) : 'N'),
                        'comments' => trim($data['comments'] ?? '')
                    ];

                    // Validate required fields
                    if (empty($cleanData['level']) || empty($cleanData['subject']) || 
                         !in_array($cleanData['is_new_concept'], ['Y', 'N'])) {
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing or invalid required fields";
                        $errorCount++;
                        continue;
                    }

                    // Check if record exists (based on level, subject, worksheet)
                    $existingConcept = NewConcept::where('level', $cleanData['level'])
                        ->where('subject', $cleanData['subject'])
                        ->where('worksheet', $cleanData['worksheet'])
                        ->first();

                    if ($existingConcept) {
                        // Update existing record
                        $existingConcept->update($cleanData);
                        $updatedCount++;
                    } else {
                        // Create new record
                        NewConcept::create($cleanData);
                        $addedCount++;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                    $errorCount++;
                }
            }

            DB::commit();

            $message = "CSV processed successfully! Added: $addedCount, Updated: $updatedCount";
            if ($errorCount > 0) {
                $message .= ", Errors: $errorCount";
            }

            return redirect()->route('admin.concepts')
                ->with('success', $message)
                ->with('csv_errors', $errors);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error uploading concept CSV: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to upload CSV: ' . $e->getMessage());
        }
    }

    public function updateConcept(Request $request, NewConcept $concept)
    {
        try {
            $request->validate([
                'level' => 'required|string|max:255',
                'subject' => 'required|string|max:255',
                'worksheet' => 'required|integer|min:1',
                'is_new_concept' => 'required|in:Y,N'
            ]);

            $concept->update($request->all());

            return redirect()->route('admin.concepts')
                ->with('success', 'New concept updated successfully');

        } catch (\Exception $e) {
            Log::error('Error updating concept: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update concept: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroyConcept(NewConcept $concept)
    {
        try {
            $concept->delete();

            return redirect()->route('admin.concepts')
                ->with('success', 'New concept deleted successfully');

        } catch (\Exception $e) {
            Log::error('Error deleting concept: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete concept: ' . $e->getMessage());
        }
    }
}