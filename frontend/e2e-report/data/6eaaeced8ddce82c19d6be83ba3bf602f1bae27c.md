# Page snapshot

```yaml
- generic [active] [ref=e1]:
  - generic [ref=e3]:
    - generic [ref=e4]:
      - paragraph [ref=e5]: Keyword Tracking
      - heading "Edit keyword" [level=1] [ref=e6]
      - paragraph [ref=e7]: Update the target URL or pause keywords that are no longer relevant.
    - generic [ref=e8]:
      - generic [ref=e10]: Edit keyword
      - generic [ref=e12]:
        - generic [ref=e13]:
          - generic [ref=e14]:
            - text: Keyword
            - textbox "Keyword" [disabled]
            - paragraph [ref=e15]: Keyword text cannot be edited.
          - generic [ref=e16]:
            - text: Target URL
            - textbox "Target URL" [ref=e17]:
              - /placeholder: https://example.com/landing
            - paragraph [ref=e18]: Optional. Must be a valid URL.
          - generic [ref=e19]:
            - text: Status
            - combobox "Status" [ref=e20]:
              - option "Active" [selected]
              - option "Paused"
        - generic [ref=e21]:
          - generic [ref=e22]:
            - button "Save changes" [ref=e23]
            - button "Cancel" [ref=e24]
          - link "Back to keyword list" [ref=e25] [cursor=pointer]:
            - /url: /sites/NaN/keywords/
  - button "Open Next.js Dev Tools" [ref=e31] [cursor=pointer]:
    - img [ref=e32]
  - alert [ref=e35]
```