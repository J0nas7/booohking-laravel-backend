<?php

namespace Tests\Unit\Actions\RegisterUser;

use App\Actions\RegisterUser\ValidateRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ValidateRegistrationTest extends TestCase
{
    use RefreshDatabase;
    protected ValidateRegistration $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ValidateRegistration();
    }

    #[Test]
    public function it_passes_with_valid_data()
    {
        $data = [
            'acceptTerms' => true,
            'User_Email' => 'test@example.com',
            'User_Password' => 'password123',
            'User_Password_confirmation' => 'password123',
            'User_Name' => 'John Doe'
        ];

        $result = $this->validator->execute($data);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_fails_with_invalid_data()
    {
        $data = [
            'acceptTerms' => false,
            'User_Email' => 'invalid-email',
            'User_Password' => 'short',
            'User_Password_confirmation' => 'mismatch',
            'User_Name' => ''
        ];

        $result = $this->validator->execute($data);

        $this->assertIsObject($result); // validator errors object
        $this->assertNotEmpty($result->all());
    }
}
