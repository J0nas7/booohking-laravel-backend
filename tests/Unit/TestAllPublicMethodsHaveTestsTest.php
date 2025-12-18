<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Unit tests to verify that all public methods has corresponding test files
 * and that these test files include the method name.
 */

class TestAllPublicMethodsHaveTestsTest extends TestCase
{
    /**
     * The array of classes and their corresponding test directories.
     *
     * @var array
     */
    protected $classTestMap = [
        // ---- Services ----
        \App\Services\AuthService::class => [
            'path' => 'tests/Unit/Services/AuthServiceTest/',
            'location' => 'per_method',
        ],
        \App\Services\BookingService::class => [
            'path' => 'tests/Unit/Services/BookingServiceTest/',
            'location' => 'per_method',
        ],

        // ---- Controllers ----
        \App\Http\Controllers\UserController::class => [
            'path' => 'tests/Feature/Controllers/UserControllerTest.php',
            'location' => 'single_file',
        ],
        \App\Http\Controllers\AuthController::class => [
            'path' => 'tests/Feature/Controllers/AuthControllerTest.php',
            'location' => 'single_file',
        ],
        \App\Http\Controllers\BookingController::class => [
            'path' => 'tests/Feature/Controllers/BookingControllerTest.php',
            'location' => 'single_file',
        ],
        \App\Http\Controllers\ProviderController::class => [
            'path' => 'tests/Feature/Controllers/ProviderControllerTest.php',
            'location' => 'single_file',
        ],
    ];

    // Test if all methods of each class have corresponding test files, and if the test file contains the method name
    /**
     * @return void
     */
    public function testAllMethodsHaveTestFilesAndMethodName()
    {
        // Arrays to collect issues
        $missingTestFiles = [];
        $invalidTestFiles = [];

        // Loop through each class and test directory
        foreach ($this->classTestMap as $class => $config) {
            $path = $config['path'];
            $location = $config['location'];
            if (!class_exists($class)) {
                $missingTestFiles[] = "Class {$$class} does not exist.";
                continue;
            }

            // Create a ReflectionClass instance for the given class
            $reflection = new \ReflectionClass($class);

            // Get all public methods of the class, filter out inherited methods,
            // and collect only the method names into an array
            $methods = collect($reflection->getMethods(\ReflectionMethod::IS_PUBLIC))
                ->filter(
                    fn($method) =>
                    $method->getDeclaringClass()->getName() === $class // Ensure the method belongs to the current class
                )
                ->map(fn($method) => $method->getName()) // Extract the method names
                ->all(); // Convert the collection to an array

            // Filter out the constructor method
            $methods = array_filter($methods, fn($method) => $method !== '__construct');

            if ($config['location'] === 'single_file') {
                // All methods are tested in one file
                if (!File::exists($config['path'])) {
                    $missingTestFiles[] = $config['path'] . " (for {$class})";
                    continue;
                }

                $fileContents = File::get($config['path']);

                foreach ($methods as $method) {
                    if (strpos($fileContents, "{$method}()") === false) {
                        $invalidTestFiles[] =
                            "{$config['path']} (missing {$method}() for {$class})";
                    }
                }

                continue;
            }

            // One test file per method
            // Loop through each method and check if the corresponding test file contains basic structure
            foreach ($methods as $method) {
                // Convert method name to the expected test file name
                $fileName = $this->getTestFileName($method);
                $fileNameFor = $fileName . ' (for ' . $class . '.php)';
                $expectedTestFile = $path . $fileName;

                // If the file doesn't exist, collect it
                if (!File::exists($expectedTestFile)) {
                    $missingTestFiles[] = $fileNameFor;
                    continue;
                }

                // Read the contents of the test file
                $fileContents = File::get($expectedTestFile);

                // If the file doesn't contain the method name, add it to the invalid test files array
                if (strpos($fileContents, $method) === false) {
                    $invalidTestFiles[] = $fileNameFor;
                }
            }
        }

        // Assert that there are no missing test files
        $this->assertEmpty(
            $missingTestFiles,
            'The following test files do not exist: ' . implode(', ', $missingTestFiles)
        );

        // Assert that there are no invalid test files
        $this->assertEmpty(
            $invalidTestFiles,
            'The following test files do not contain the method name: ' . implode(', ', $invalidTestFiles)
        );
    }

    // Get the expected test file name for a given method.
    /**
     * @param string $method
     * @return string
     */
    protected function getTestFileName(string $method): string
    {
        // Convention: Test file names are the method name with 'Test' appended.
        // Example: 'registerUser' -> 'RegisterUserTest.php'
        return ucfirst($method) . 'Test.php';
    }
}
