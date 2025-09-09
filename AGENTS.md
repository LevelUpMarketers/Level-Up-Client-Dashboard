# Agent Instructions

This repository contains a WordPress plugin for a client management dashboard.

## Goals
- Write WordPress-compliant code.
- Prioritize security: validate and sanitize input, check capabilities.
- Be efficient and performant; avoid duplicate code.
- Use custom database tables rather than custom post types.
- Load no assets on the front end unless a plugin shortcode is present.

## Testing
- After modifying PHP files, run `php -l` on each changed file.

## Documentation
- When database structures change, update existing documentation to reflect the new schema. Overwrite outdated docs rather than creating redundant files.

