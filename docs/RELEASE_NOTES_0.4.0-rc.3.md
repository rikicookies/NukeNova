# NovaNuke 0.4.0-rc.3

RC.3 is the mobile-hardening release candidate.

It fixes the NovaModern administration sidebar on phones, improves responsive administration tables and empty states, and adds persistent administration-navigation ordering. The navigation order is stored in normal NovaNuke settings, so no database migration is required.

## Acceptance focus

- Open the NovaModern administration sidebar at 320, 375, 390 and 430 CSS pixels and scroll to the final navigation item.
- Confirm the page behind the open sidebar does not scroll.
- Check News, Pages, Web Links, Downloads, Polls, Statistics, Media, Wiki, Users and Memberships administration screens on a phone-sized viewport.
- Verify wide tables remain contained and their empty states remain readable without page-level horizontal scrolling.
- Reorder administration navigation at `/admin/menus`, save, reload and confirm the sidebar uses the new order.
- Run the normal checkpoint and installed-site checks before promoting RC.3.
