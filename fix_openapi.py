import re

file_path = r"C:\Users\Raitis\.openclaw\workspace\PROJECT-SEOINDICATOR\backend\src\api\openapi.yaml"

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace Unauthorized response ref
content = re.sub(
    r"          '401':\s*\$ref: '#/components/responses/Unauthorized'",
    """          '401':
            description: Authentication required
            content:
              application/json:
                schema:
                  $ref: '#/components/schemas/Error'""",
    content
)

# Replace Forbidden response ref
content = re.sub(
    r"          '403':\s*\$ref: '#/components/responses/Forbidden'",
    """          '403':
            description: Access forbidden
            content:
              application/json:
                schema:
                  $ref: '#/components/schemas/Error'""",
    content
)

# Replace NotFound response ref
content = re.sub(
    r"          '404':\s*\$ref: '#/components/responses/NotFound'",
    """          '404':
            description: Resource not found
            content:
              application/json:
                schema:
                  $ref: '#/components/schemas/Error'""",
    content
)

# Replace ValidationError response ref
content = re.sub(
    r"          '422':\s*\$ref: '#/components/responses/ValidationError'",
    """          '422':
            description: Validation error
            content:
              application/json:
                schema:
                  $ref: '#/components/schemas/ValidationError'""",
    content
)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Done")
