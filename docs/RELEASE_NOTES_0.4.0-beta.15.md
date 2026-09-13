# NovaNuke 0.4.0-beta.15

Beta 15 resolves the final three unit-contract failures reported by the Beta 14 Windows checkpoint.

The fixes align stale tests with the current dedicated Memberships admin route, prevent `/account/deleted` from being misclassified as a state-changing GET route, and keep the supported upgrade-source chain current.

No new feature or database migration is introduced.

Run `composer test:checkpoint`. If the unit phase passes, the Composer checkpoint can continue into its integration phase.
