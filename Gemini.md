# Gemini Project

This file outlines the project structure and implementation details for integrating a LangGraph-powered AI assistant into the existing Express Sale application.

## Architecture

The AI assistant will be implemented as a separate service using LangGraph. The Laravel backend will communicate with this service.

### 1. User Input Processing

The user input will first be classified to determine if it's in French (fr) or Malagasy (mg). This will be done using two distinct models.

### 2. FAQ Retrieval (RAG)

- If the input is in French, the system will search a French FAQ knowledge base.
- If the input is in Malagasy, the system will search a Malagasy FAQ knowledge base.
- The system will also be able to ingest PDF documents to create a searchable knowledge base (RAG).

### 3. Response Generation

If a relevant FAQ is found, it will be returned to the user. Otherwise, the query will be passed to a generative model.

## Implementation Plan

1.  **Create `Gemini.md`:** Document the project architecture and plan. (Done)
2.  **Set up LangGraph Service:** Initialize a new LangGraph project.
3.  **Language Detection:** Implement a language detection node in the LangGraph graph.
4.  **RAG Implementation:**
    *   Create a script to ingest FAQ data (from text files or a database).
    *   Create a script to ingest PDF documents.
    *   Implement the RAG retrieval logic within the LangGraph service.
5.  **Laravel Integration:**
    *   Create an API endpoint in Laravel to receive user queries.
    *   This endpoint will forward the queries to the LangGraph service.
    *   The response from the LangGraph service will be returned to the user.
