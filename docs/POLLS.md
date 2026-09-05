# Polls module

Polls 1.0.0 provides scheduled single-choice and multiple-choice community polls at `/polls`, administration at `/admin/polls`, and an active-poll block.

## Installation and block

Install and enable Polls from `/admin/modules`. Its migration creates a block named `Active poll` in the right sidebar. The ordinary Blocks panel can change its title, position, order, schedule, page rules, role visibility and enabled state without converting it to administrator HTML.

The core's `block.rendering` hook allows trusted modules to render non-HTML block types. If Polls is disabled or no poll is active in its configured date window, the block produces no output.

## Voting rules

- Polls have draft, active and closed states.
- Optional UTC start and end dates further control voting.
- A poll contains 2–20 case-insensitively unique options.
- Single-choice polls accept exactly one option.
- Multiple-choice polls enforce the configured maximum.
- Options cannot change after the first vote, protecting existing results.
- Result percentages mean the percentage of voters who selected each option; multiple-choice totals can exceed 100% when added together.

Authenticated voters are deduplicated by an HMAC derived from their user ID. Guests receive a random session identity combined with their network address, also stored only as an HMAC. Raw IP addresses, user agents and session tokens are not stored in poll tables.

## Guest-vote limitation

Guest duplication cannot be prevented completely. Clearing cookies, changing browsers or networks, or deliberately distributing traffic can permit additional votes. Conversely, a network change during a session can make the same guest appear new. These controls deter ordinary repeats; they are not suitable for elections, legal decisions, prizes or other high-stakes voting.
