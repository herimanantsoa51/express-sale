from langchain_core.prompts import PromptTemplate

LANG_DETECT_PROMPT = PromptTemplate.from_template(
    "Detect the language of the following text. Respond with 'fr' for French, 'mg' for Malagasy, and 'unknown' for other languages.\n\nText: {text}"
)
