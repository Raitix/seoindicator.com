<?php

namespace Tests\Unit\Services\AI\DTOs;

use Tests\TestCase;
use App\Services\AI\DTOs\AnalysisResult;

/**
 * Unit tests for AnalysisResult DTO.
 * 
 * Tests the data transfer object that holds structured AI analysis results
 * including overall score, summary, category scores, indicators, and priorities.
 * 
 * @see TASK-31-5
 * @see SPEC-031
 */
class AnalysisResultTest extends TestCase
{
    // =========================================================================
    // Creation Tests (CR)
    // =========================================================================

    /** @test CR-001: Can create AnalysisResult from valid array */
    public function can_create_from_valid_array(): void
    {
        $data = $this->getValidData();

        $result = AnalysisResult::fromArray($data);

        $this->assertInstanceOf(AnalysisResult::class, $result);
    }

    /** @test CR-002: fromArray populates all properties */
    public function from_array_populates_all_properties(): void
    {
        $data = $this->getValidData();

        $result = AnalysisResult::fromArray($data);

        $this->assertEquals($data['overall_score'], $result->overallScore);
        $this->assertEquals($data['overall_summary'], $result->overallSummary);
        $this->assertEquals($data['category_scores'], $result->categoryScores);
        $this->assertEquals($data['indicators'], $result->indicators);
        $this->assertEquals($data['top_priorities'], $result->topPriorities);
        $this->assertEquals($data['model'], $result->model);
        $this->assertEquals($data['prompt_tokens'], $result->promptTokens);
        $this->assertEquals($data['response_tokens'], $result->responseTokens);
    }

    /** @test CR-003: fromArray uses defaults for missing optional fields */
    public function from_array_uses_defaults_for_missing_optionals(): void
    {
        $data = [
            'overall_score' => 75,
            'overall_summary' => 'Test summary with sufficient length for validation.',
            'indicators' => $this->getValidIndicators(),
        ];

        $result = AnalysisResult::fromArray($data);

        $this->assertEquals([], $result->categoryScores);
        $this->assertEquals([], $result->topPriorities);
        $this->assertEquals('unknown', $result->model);
        $this->assertEquals(0, $result->promptTokens);
        $this->assertEquals(0, $result->responseTokens);
    }

    /** @test CR-004: fromArray handles null category_scores */
    public function from_array_handles_null_category_scores(): void
    {
        $data = $this->getValidData();
        $data['category_scores'] = null;

        $result = AnalysisResult::fromArray($data);

        $this->assertEquals([], $result->categoryScores);
    }

    /** @test CR-005: fromArray handles null top_priorities */
    public function from_array_handles_null_top_priorities(): void
    {
        $data = $this->getValidData();
        $data['top_priorities'] = null;

        $result = AnalysisResult::fromArray($data);

        $this->assertEquals([], $result->topPriorities);
    }

    // =========================================================================
    // Property Access Tests (PA)
    // =========================================================================

    /** @test PA-001: Can access overallScore */
    public function can_access_overall_score(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertEquals(75, $result->overallScore);
    }

    /** @test PA-002: Can access overallSummary */
    public function can_access_overall_summary(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertEquals('Test summary with sufficient length for validation purposes.', $result->overallSummary);
    }

    /** @test PA-003: Can access categoryScores */
    public function can_access_category_scores(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsArray($result->categoryScores);
        $this->assertEquals(80, $result->categoryScores['content']);
    }

    /** @test PA-004: Can access indicators */
    public function can_access_indicators(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsArray($result->indicators);
        $this->assertCount(17, $result->indicators);
    }

    /** @test PA-005: Can access topPriorities */
    public function can_access_top_priorities(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsArray($result->topPriorities);
        $this->assertEquals(['title', 'meta_description'], $result->topPriorities);
    }

    /** @test PA-006: Can access model */
    public function can_access_model(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertEquals('llama-3.1-8b-instruct', $result->model);
    }

    /** @test PA-007: Can access promptTokens */
    public function can_access_prompt_tokens(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertEquals(500, $result->promptTokens);
    }

    /** @test PA-008: Can access responseTokens */
    public function can_access_response_tokens(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertEquals(1000, $result->responseTokens);
    }

    // =========================================================================
    // Immutability Tests (IM)
    // =========================================================================

    /** @test IM-001: overallScore is readonly */
    public function overall_score_is_readonly(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->expectException(\Error::class);
        $result->overallScore = 999;
    }

    /** @test IM-002: overallSummary is readonly */
    public function overall_summary_is_readonly(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->expectException(\Error::class);
        $result->overallSummary = 'modified';
    }

    /** @test IM-003: categoryScores is readonly */
    public function category_scores_is_readonly(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->expectException(\Error::class);
        $result->categoryScores = [];
    }

    /** @test IM-004: indicators is readonly */
    public function indicators_is_readonly(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->expectException(\Error::class);
        $result->indicators = [];
    }

    // =========================================================================
    // Serialization Tests (SE)
    // =========================================================================

    /** @test SE-001: toArray returns array representation */
    public function to_array_returns_array(): void
    {
        $data = $this->getValidData();
        $result = AnalysisResult::fromArray($data);

        $array = $result->toArray();

        $this->assertIsArray($array);
    }

    /** @test SE-002: toArray contains all fields */
    public function to_array_contains_all_fields(): void
    {
        $data = $this->getValidData();
        $result = AnalysisResult::fromArray($data);

        $array = $result->toArray();

        $this->assertArrayHasKey('overall_score', $array);
        $this->assertArrayHasKey('overall_summary', $array);
        $this->assertArrayHasKey('category_scores', $array);
        $this->assertArrayHasKey('indicators', $array);
        $this->assertArrayHasKey('top_priorities', $array);
        $this->assertArrayHasKey('model', $array);
        $this->assertArrayHasKey('prompt_tokens', $array);
        $this->assertArrayHasKey('response_tokens', $array);
    }

    /** @test SE-003: toArray values match original data */
    public function to_array_values_match_original(): void
    {
        $data = $this->getValidData();
        $result = AnalysisResult::fromArray($data);

        $array = $result->toArray();

        $this->assertEquals($data['overall_score'], $array['overall_score']);
        $this->assertEquals($data['overall_summary'], $array['overall_summary']);
        $this->assertEquals($data['indicators'], $array['indicators']);
    }

    /** @test SE-004: Can serialize to JSON */
    public function can_serialize_to_json(): void
    {
        $data = $this->getValidData();
        $result = AnalysisResult::fromArray($data);

        $json = json_encode($result);

        $this->assertIsString($json);
        $this->assertStringContainsString('overall_score', $json);
    }

    /** @test SE-005: Round-trip serialization preserves data */
    public function round_trip_preserves_data(): void
    {
        $originalData = $this->getValidData();
        $result = AnalysisResult::fromArray($originalData);

        $json = json_encode($result);
        $decoded = json_decode($json, true);
        $restored = AnalysisResult::fromArray($decoded);

        $this->assertEquals($result->overallScore, $restored->overallScore);
        $this->assertEquals($result->overallSummary, $restored->overallSummary);
    }

    // =========================================================================
    // Edge Cases (EC)
    // =========================================================================

    /** @test EC-001: Handles unicode in summary */
    public function handles_unicode_in_summary(): void
    {
        $data = $this->getValidData();
        $data['overall_summary'] = '日本語テスト 中文 العربية 🎉';

        $result = AnalysisResult::fromArray($data);

        $this->assertStringContainsString('日本語', $result->overallSummary);
    }

    /** @test EC-002: Handles very long summary */
    public function handles_very_long_summary(): void
    {
        $data = $this->getValidData();
        $data['overall_summary'] = str_repeat('a', 500);

        $result = AnalysisResult::fromArray($data);

        $this->assertEquals(500, strlen($result->overallSummary));
    }

    /** @test EC-003: Handles special characters in summary */
    public function handles_special_characters_in_summary(): void
    {
        $data = $this->getValidData();
        $data['overall_summary'] = 'Test "quotes" and \'apostrophes\' and <html>';

        $result = AnalysisResult::fromArray($data);

        $this->assertStringContainsString('"quotes"', $result->overallSummary);
    }

    /** @test EC-004: Handles empty indicators array */
    public function handles_empty_indicators(): void
    {
        $data = $this->getValidData();
        $data['indicators'] = [];

        $result = AnalysisResult::fromArray($data);

        $this->assertEquals([], $result->indicators);
    }

    /** @test EC-005: Handles indicator with all optional fields */
    public function handles_indicator_with_all_optionals(): void
    {
        $indicators = $this->getValidIndicators();
        $indicators[0]['details'] = ['extra' => 'data'];
        $indicators[0]['suggestions'] = ['Suggestion 1', 'Suggestion 2'];

        $data = $this->getValidData();
        $data['indicators'] = $indicators;

        $result = AnalysisResult::fromArray($data);

        $this->assertEquals(['extra' => 'data'], $result->indicators[0]['details']);
        $this->assertCount(2, $result->indicators[0]['suggestions']);
    }

    /** @test EC-006: Handles large token counts */
    public function handles_large_token_counts(): void
    {
        $data = $this->getValidData();
        $data['prompt_tokens'] = 100000;
        $data['response_tokens'] = 200000;

        $result = AnalysisResult::fromArray($data);

        $this->assertEquals(100000, $result->promptTokens);
        $this->assertEquals(200000, $result->responseTokens);
    }

    /** @test EC-007: Handles zero token counts */
    public function handles_zero_token_counts(): void
    {
        $data = $this->getValidData();
        $data['prompt_tokens'] = 0;
        $data['response_tokens'] = 0;

        $result = AnalysisResult::fromArray($data);

        $this->assertEquals(0, $result->promptTokens);
        $this->assertEquals(0, $result->responseTokens);
    }

    // =========================================================================
    // Type Safety Tests (TS)
    // =========================================================================

    /** @test TS-001: overallScore is integer */
    public function overall_score_is_integer(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsInt($result->overallScore);
    }

    /** @test TS-002: overallSummary is string */
    public function overall_summary_is_string(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsString($result->overallSummary);
    }

    /** @test TS-003: categoryScores is array */
    public function category_scores_is_array(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsArray($result->categoryScores);
    }

    /** @test TS-004: indicators is array */
    public function indicators_is_array(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsArray($result->indicators);
    }

    /** @test TS-005: topPriorities is array */
    public function top_priorities_is_array(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsArray($result->topPriorities);
    }

    /** @test TS-006: model is string */
    public function model_is_string(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsString($result->model);
    }

    /** @test TS-007: promptTokens is integer */
    public function prompt_tokens_is_integer(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsInt($result->promptTokens);
    }

    /** @test TS-008: responseTokens is integer */
    public function response_tokens_is_integer(): void
    {
        $result = AnalysisResult::fromArray($this->getValidData());

        $this->assertIsInt($result->responseTokens);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function getValidData(): array
    {
        return [
            'overall_score' => 75,
            'overall_summary' => 'Test summary with sufficient length for validation purposes.',
            'category_scores' => [
                'content' => 80,
                'technical' => 70,
                'accessibility' => 75,
                'performance' => 72,
            ],
            'indicators' => $this->getValidIndicators(),
            'top_priorities' => ['title', 'meta_description'],
            'model' => 'llama-3.1-8b-instruct',
            'prompt_tokens' => 500,
            'response_tokens' => 1000,
        ];
    }

    private function getValidIndicators(): array
    {
        $indicatorIds = [
            'title', 'meta_description', 'meta_keywords', 'robots_txt', 'sitemap',
            'headings', 'image_alt', 'internal_links', 'external_links', 'url_structure',
            'canonical_url', 'https', 'mobile_friendly', 'page_speed', 'structured_data',
            'social_tags', 'content_quality',
        ];

        $indicators = [];
        foreach ($indicatorIds as $id) {
            $indicators[] = [
                'id' => $id,
                'name' => ucfirst(str_replace('_', ' ', $id)),
                'score' => rand(0, 3),
                'passed' => (bool)rand(0, 1),
            ];
        }

        return $indicators;
    }
}
