<?php

namespace Tests\Unit\Services\SEO\Checkers;

use App\Services\SEO\Checkers\Suggestion;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Suggestion DTO
 * 
 * @see TASK-18-3
 * @see TEST-18-3
 */
class SuggestionTest extends TestCase
{
    /**
     * Test create with all fields.
     * 
     * @test
     */
    public function create_with_all_fields(): void
    {
        $suggestion = new Suggestion(
            type: 'warning',
            message: 'Title tag is too short.',
            action: 'Add more descriptive text.',
            priority: 2
        );

        $this->assertEquals('warning', $suggestion->type);
        $this->assertEquals('Title tag is too short.', $suggestion->message);
        $this->assertEquals('Add more descriptive text.', $suggestion->action);
        $this->assertEquals(2, $suggestion->priority);
    }

    /**
     * Test DTO is immutable.
     * 
     * @test
     */
    public function is_immutable(): void
    {
        $suggestion = $this->createDefaultSuggestion();

        $this->expectException(\Error::class);
        $suggestion->message = 'Modified';
    }

    /**
     * Test getType() returns string.
     * 
     * @test
     */
    public function get_type_returns_string(): void
    {
        $suggestion = $this->createDefaultSuggestion();

        $this->assertIsString($suggestion->type);
    }

    /**
     * Test getMessage() returns string.
     * 
     * @test
     */
    public function get_message_returns_string(): void
    {
        $suggestion = $this->createDefaultSuggestion();

        $this->assertIsString($suggestion->message);
    }

    /**
     * Test getAction() returns string.
     * 
     * @test
     */
    public function get_action_returns_string(): void
    {
        $suggestion = $this->createDefaultSuggestion();

        $this->assertIsString($suggestion->action);
    }

    /**
     * Test getPriority() returns int.
     * 
     * @test
     */
    public function get_priority_returns_int(): void
    {
        $suggestion = $this->createDefaultSuggestion();

        $this->assertIsInt($suggestion->priority);
    }

    /**
     * Test toArray() serializes correctly.
     * 
     * @test
     */
    public function to_array_serializes_correctly(): void
    {
        $suggestion = $this->createDefaultSuggestion();

        $array = $suggestion->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('action', $array);
        $this->assertArrayHasKey('priority', $array);
    }

    /**
     * Test toJson() serializes correctly.
     * 
     * @test
     */
    public function to_json_serializes_correctly(): void
    {
        $suggestion = $this->createDefaultSuggestion();

        $json = $suggestion->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals($suggestion->message, $decoded['message']);
    }

    /**
     * Test fromArray() creates DTO.
     * 
     * @test
     */
    public function from_array_creates_dto(): void
    {
        $data = [
            'type' => 'warning',
            'message' => 'Test message',
            'action' => 'Test action',
            'priority' => 1,
        ];

        $suggestion = Suggestion::fromArray($data);

        $this->assertInstanceOf(Suggestion::class, $suggestion);
        $this->assertEquals('warning', $suggestion->type);
    }

    /**
     * Test type validation - warning.
     * 
     * @test
     */
    public function type_validation_warning(): void
    {
        $suggestion = new Suggestion(
            type: 'warning',
            message: 'Test',
            action: null,
            priority: 1
        );

        $this->assertEquals('warning', $suggestion->type);
    }

    /**
     * Test type validation - error.
     * 
     * @test
     */
    public function type_validation_error(): void
    {
        $suggestion = new Suggestion(
            type: 'error',
            message: 'Test',
            action: null,
            priority: 1
        );

        $this->assertEquals('error', $suggestion->type);
    }

    /**
     * Test type validation - info.
     * 
     * @test
     */
    public function type_validation_info(): void
    {
        $suggestion = new Suggestion(
            type: 'info',
            message: 'Test',
            action: null,
            priority: 1
        );

        $this->assertEquals('info', $suggestion->type);
    }

    /**
     * Test invalid type throws exception.
     * 
     * @test
     */
    public function invalid_type_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Suggestion(
            type: 'invalid_type',
            message: 'Test',
            action: null,
            priority: 1
        );
    }

    /**
     * Test empty message throws exception.
     * 
     * @test
     */
    public function empty_message_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Suggestion(
            type: 'warning',
            message: '',
            action: null,
            priority: 1
        );
    }

    /**
     * Test optional action field.
     * 
     * @test
     */
    public function optional_action_field(): void
    {
        $suggestion = new Suggestion(
            type: 'warning',
            message: 'Test message',
            action: null,
            priority: 1
        );

        $this->assertNull($suggestion->action);
    }

    /**
     * Test default priority.
     * 
     * @test
     */
    public function default_priority(): void
    {
        $suggestion = new Suggestion(
            type: 'warning',
            message: 'Test',
            action: null
        );

        $this->assertEquals(1, $suggestion->priority);
    }

    /**
     * Test isWarning helper.
     * 
     * @test
     */
    public function is_warning_helper(): void
    {
        $warning = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 1);
        $error = new Suggestion(type: 'error', message: 'Test', action: null, priority: 1);

        $this->assertTrue($warning->isWarning());
        $this->assertFalse($error->isWarning());
    }

    /**
     * Test isError helper.
     * 
     * @test
     */
    public function is_error_helper(): void
    {
        $warning = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 1);
        $error = new Suggestion(type: 'error', message: 'Test', action: null, priority: 1);

        $this->assertFalse($warning->isError());
        $this->assertTrue($error->isError());
    }

    /**
     * Test isInfo helper.
     * 
     * @test
     */
    public function is_info_helper(): void
    {
        $info = new Suggestion(type: 'info', message: 'Test', action: null, priority: 1);
        $warning = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 1);

        $this->assertTrue($info->isInfo());
        $this->assertFalse($warning->isInfo());
    }

    /**
     * Helper: Create default suggestion.
     */
    private function createDefaultSuggestion(): Suggestion
    {
        return new Suggestion(
            type: 'warning',
            message: 'Test suggestion',
            action: 'Test action',
            priority: 1
        );
    }
}
