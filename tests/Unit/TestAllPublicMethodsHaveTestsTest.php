<?php

namespace Tests\Unit;

use App\Http\Controllers\{
    AuthController,
    BookingController,
    ProviderController,
    ProviderWorkingHourController,
    ServiceController,
    UserController
};
use App\Services\{
    AuthService,
    BookingService,
    UserService
};
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
        // ---- Actions ----
        'Actions/*' => [
            'path' => 'tests/Unit/Actions/',
            'location' => 'per_method',
        ],

        // ---- Services ----
        AuthService::class => [
            'path' => 'tests/Unit/Services/AuthServiceTest/',
            'location' => 'per_method',
        ],
        UserService::class => [
            'path' => 'tests/Unit/Services/UserServiceTest/',
            'location' => 'per_method',
        ],
        BookingService::class => [
            'path' => 'tests/Unit/Services/BookingServiceTest/',
            'location' => 'per_method',
        ],

        // ---- Controllers ----
        AuthController::class => [
            'path' => 'tests/Feature/Controllers/AuthControllerTest.php',
            'location' => 'single_file',
        ],
        BookingController::class => [
            'path' => 'tests/Feature/Controllers/BookingControllerTest.php',
            'location' => 'single_file',
        ],
        ProviderController::class => [
            'path' => 'tests/Feature/Controllers/ProviderControllerTest.php',
            'location' => 'single_file',
        ],
        ProviderWorkingHourController::class => [
            'path' => 'tests/Feature/Controllers/ProviderWorkingHourControllerTest.php',
            'location' => 'single_file',
        ],
        ServiceController::class => [
            'path' => 'tests/Feature/Controllers/ServiceControllerTest.php',
            'location' => 'single_file',
        ],
        UserController::class => [
            'path' => 'tests/Feature/Controllers/UserControllerTest.php',
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
            // If class contains wildcard, handle it differently
            if (strpos($class, '*') !== false) {
                $this->handleWildcardClass($class, $config, $missingTestFiles, $invalidTestFiles);
                continue;
            }

            // Regular class processing
            $this->handleRegularClass($class, $config, $missingTestFiles, $invalidTestFiles);
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

    /**
     * Handle classes with wildcard patterns.
     */
    private function handleWildcardClass(string $class, array $config, array &$missingTestFiles, array &$invalidTestFiles)
    {
        $directory = str_replace('*', '', $class);
        $files = File::allFiles(app_path($directory));

        foreach ($files as $file) {
            $classFullName = $this->getClassFullNameFromFile($file);
            if (!class_exists($classFullName)) {
                $missingTestFiles[] = "Class {$classFullName} does not exist.";
                continue;
            }

            $reflection = new \ReflectionClass($classFullName);
            $methods = $this->getClassMethods($reflection);

            // Check test file for methods
            $this->checkRelativeTestFilesPerMethod($class, $classFullName, $methods, $file, $config, $missingTestFiles, $invalidTestFiles);
        }
    }

    /**
     * Handle regular class without wildcard patterns.
     */
    private function handleRegularClass(string $class, array $config, array &$missingTestFiles, array &$invalidTestFiles)
    {
        if (!class_exists($class)) {
            $missingTestFiles[] = "Class {$class} does not exist.";
            return;
        }

        $reflection = new \ReflectionClass($class);
        $methods = $this->getClassMethods($reflection);

        if ($config['location'] === 'single_file') {
            // Check all methods in one file
            $this->checkTestFileForMethodsInSingleFile($class, $methods, $config, $missingTestFiles, $invalidTestFiles);
        } else {
            // One test file per method
            $this->checkTestFilesPerMethod($class, $methods, $config, $missingTestFiles, $invalidTestFiles);
        }
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

    /**
     * Extract the full class name from a file path.
     */
    private function getClassFullNameFromFile(\Symfony\Component\Finder\SplFileInfo $file): string
    {
        $classFullName = 'App\\' . str_replace(
            '/',
            '\\',
            substr($file->getPathname(), strlen(app_path() . DIRECTORY_SEPARATOR))
        );
        return rtrim($classFullName, '.php');
    }

    /**
     * Extract methods from a given ReflectionClass, excluding constructors.
     */
    private function getClassMethods(\ReflectionClass $reflection): array
    {
        return collect($reflection->getMethods(\ReflectionMethod::IS_PUBLIC))
            ->filter(fn($method) => $method->getDeclaringClass()->getName() === $reflection->getName())
            ->map(fn($method) => $method->getName())
            ->reject(fn($method) => $method === '__construct')
            ->all();
    }

    /**
     * Check if a test file for methods exists and contains the expected methods.
     */
    private function checkRelativeTestFilesPerMethod(string $class, string $classFullName, array $methods, \Symfony\Component\Finder\SplFileInfo $file, array $config, array &$missingTestFiles, array &$invalidTestFiles)
    {
        $relativePath = $this->getTestFileRelativePath($file, $class, $config);
        $testFilePath = $config['path'] . $relativePath;

        if (!File::exists($testFilePath)) {
            $missingTestFiles[] = "{$testFilePath} (for {$classFullName})";
            return;
        }
    }

    /**
     * Generate the relative test file path for a given file.
     */
    private function getTestFileRelativePath(\Symfony\Component\Finder\SplFileInfo $file, string $class, array $config): string
    {
        $fullPath = $file->getPathname();
        $directory = str_replace('/*', '', $class);
        $relativePath = substr($fullPath, strlen(app_path() . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR));
        return str_replace('.php', 'Test.php', $relativePath);
    }

    /**
     * Check all methods in a single test file.
     */
    private function checkTestFileForMethodsInSingleFile(string $class, array $methods, array $config, array &$missingTestFiles, array &$invalidTestFiles)
    {
        $filePath = $config['path'];

        if (!File::exists($filePath)) {
            $missingTestFiles[] = "{$filePath} (for {$class})";
            return;
        }

        $fileContents = File::get($filePath);
        foreach ($methods as $method) {
            if (strpos($fileContents, "{$method}()") === false) {
                $invalidTestFiles[] = "{$filePath} (missing {$method}() for {$class})";
            }
        }
    }

    /**
     * Check test files per method.
     */
    private function checkTestFilesPerMethod(string $class, array $methods, array $config, array &$missingTestFiles, array &$invalidTestFiles)
    {
        foreach ($methods as $method) {
            $fileName = $this->getTestFileName($method);
            $fileNameFor = "{$fileName} (for {$class}.php)";
            $expectedTestFile = $config['path'] . $fileName;

            if (!File::exists($expectedTestFile)) {
                $missingTestFiles[] = $fileNameFor;
                continue;
            }

            $fileContents = File::get($expectedTestFile);
            if (strpos($fileContents, $method) === false) {
                $invalidTestFiles[] = $fileNameFor;
            }
        }
    }
}
