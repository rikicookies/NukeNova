# Known Issues and Technical Debt

## Dynamic blocks are postponed

Dynamic Polls and Statistics blocks are treated as provisional in the `0.2.0-alpha` line. The core now registers mutable block regions before provider templates render and isolates provider exceptions, but the complete matrix of dynamic providers, page types and both bundled themes has not completed acceptance testing.

If a dynamic provider fails, NovaNuke should omit that block and write a sanitized log entry. A Blocks-only failure must not block unrelated feature work. Further improvements to provider behavior, sidebar regions or advanced block placement are postponed unless they prevent another prioritized feature from running.

## Responsive block order

NovaModern 1.1.0 keeps block columns readable on small screens, but their final responsive ordering has not been designed yet. Depending on which positions are populated, left-sidebar blocks may appear before the primary content and right-sidebar blocks after it. A future navigation/layout checkpoint will define configurable or content-first ordering without changing block provider logic.
