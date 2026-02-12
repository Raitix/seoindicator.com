import unittest
from ai_title_tag_indicator import analyze_title_tags

class TestAITitleTagIndicator(unittest.TestCase):
    def test_extract_title_tag_data(self):
        html_content = "<title>Example Title</title>"
        result = analyze_title_tags(html_content)
        self.assertEqual(result["extracted_title"], "Example Title")

    def test_ai_suggestion_format(self):
        mock_response = {"ai_suggestion": "Make your title more descriptive."}
        result = analyze_title_tags("<title>Test</title>")
        self.assertEqual(result["ai_suggestion"], mock_response["ai_suggestion"])

if __name__ == "__main__":
    unittest.main()