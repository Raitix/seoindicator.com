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
     * Test type "warning" is valid.
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
     * Test type "error" is valid.
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
     * Test type "info" is valid.
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
     * Test whitespace-only message throws exception.
     * 
     * @test
     */
    public function whitespace_message_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Suggestion(
            type: 'warning',
            message: '   ',
            action: null,
            priority: 1
        );
    }

    /**
     * Test optional action field (null).
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
     * Test default priority is 1.
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
     * Test priority validation (1-3).
     * 
     * @test
     */
    public function priority_validation_valid(): void
    {
        $low = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 1);
        $medium = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 2);
        $high = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 3);

        $this->assertEquals(1, $low->priority);
        $this->assertEquals(2, $medium->priority);
        $this->assertEquals(3, $high->priority);
    }

    /**
     * Test invalid priority (0) throws exception.
     * 
     * @test
     */
    public function invalid_priority_zero_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Suggestion(
            type: 'warning',
            message: 'Test',
            action: null,
            priority: 0
        );
    }

    /**
     * Test invalid priority (4) throws exception.
     * 
     * @test
     */
    public function invalid_priority_four_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Suggestion(
            type: 'warning',
            message: 'Test',
            action: null,
            priority: 4
        );
    }

    /**
     * Test toArray() serialization.
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
     * Test toJson() serialization.
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
            'priority' => 2,
        ];

        $suggestion = Suggestion::fromArray($data);

        $this->assertInstanceOf(Suggestion::class, $suggestion);
        $this->assertEquals('warning', $suggestion->type);
        $this->assertEquals('Test message', $suggestion->message);
        $this->assertEquals('Test action', $suggestion->action);
        $this->assertEquals(2, $suggestion->priority);
    }

    /**
     * Test fromArray() with missing optional fields.
     * 
     * @test
     */
    public function from_array_with_missing_optional_fields(): void
    {
        $data = [
            'type' => 'warning',
            'message' => 'Test message',
        ];

        $suggestion = Suggestion::fromArray($data);

        $this->assertNull($suggestion->action);
        $this->assertEquals(1, $suggestion->priority);
    }

    /**
     * Test isWarning() helper.
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
     * Test isError() helper.
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
     * Test isInfo() helper.
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
     * Test isHighPriority() helper.
     * 
     * @test
     */
    public function is_high_priority_helper(): void
    {
        $high = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 3);
        $low = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 1);

        $this->assertTrue($high->isHighPriority());
        $this->assertFalse($low->isHighPriority());
    }

    /**
     * Test isMediumPriority() helper.
     * 
     * @test
     */
    public function is_medium_priority_helper(): void
    {
        $medium = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 2);
        $low = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 1);

        $this->assertTrue($medium->isMediumPriority());
        $this->assertFalse($low->isMediumPriority());
    }

    /**
     * Test isLowPriority() helper.
     * 
     * @test
     */
    public function is_low_priority_helper(): void
    {
        $low = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 1);
        $high = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 3);

        $this->assertTrue($low->isLowPriority());
        $this->assertFalse($high->isLowPriority());
    }

    /**
     * Test getPriorityLabel() helper.
     * 
     * @test
     */
    public function get_priority_label(): void
    {
        $low = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 1);
        $medium = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 2);
        $high = new Suggestion(type: 'warning', message: 'Test', action: null, priority: 3);

        $this->assertEquals('Low', $low->getPriorityLabel());
        $this->assertEquals('Medium', $medium->getPriorityLabel());
        $this->assertEquals('High', $high->getPriorityLabel());
    }

    /**
     * Test long message handling.
     * 
     * @test
     */
    public function long_message_handling(): void
    {
        $longMessage = str_repeat('This is a long message. ', 50);

        $suggestion = new Suggestion(
            type: 'warning',
            message: $longMessage,
            action: null,
            priority: 1
        );

        $this->assertEquals($longMessage, $suggestion->message);
    }

    /**
     * Test unicode in message.
     * 
     * @test
     */
    public function unicode_in_message(): void
    {
        $suggestion = new Suggestion(
            type: 'warning',
            message: 'Unicode test: 你好世界 🌍 émojis',
            action: null,
            priority: 1
        );

        $this->assertStringContainsString('你好世界', $suggestion->message);
        $this->assertStringContainsString('🌍', $suggestion->message);
    }

    /**
     * Test round-trip serialization.
     * 
     * @test
     */
    public function round_trip_serialization(): void
    {
        $original = new Suggestion(
            type: 'warning',
            message: 'Test message',
            action: 'Test action',
            priority: 2
        );

        $array = $original->toArray();
        $restored = Suggestion::fromArray($array);

        $this->assertEquals($original->type, $restored->type);
        $this->assertEquals($original->message, $restored->message);
        $this->assertEquals($original->action, $restored->action);
        $this->assertEquals($original->priority, $restored->priority);
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
