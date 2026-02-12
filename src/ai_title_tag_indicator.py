def analyze_title_tags(html_content):
    from bs4 import BeautifulSoup
    import openai

    # Parse HTML
    soup = BeautifulSoup(html_content, "html.parser")
    extracted_title = soup.title.string if soup.title else None
    
    # Mock AI Analysis (to be replaced with OpenAI API calls)
    ai_suggestion = "Make your title more descriptive."  # Replace this with actual AI call logic
    
    return {
        "extracted_title": extracted_title,
        "ai_suggestion": ai_suggestion
    }

# Example: analyze_title_tags('<title>Example</title>')