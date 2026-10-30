// spec_sec_2026_02_21_1.test.js

const fs = require('fs');
const { execSync } = require('child_process');

describe('SPEC-SEC-2026-02-21-1: Remove Hardcoded Secrets from Repository Files', () => {
    test('Ensure .env is ignored by Git', () => {
        const gitIgnoreContent = fs.readFileSync('.gitignore', 'utf8');
        expect(gitIgnoreContent).toContain('.env');

        try {
            execSync('git add .env');
            throw new Error('.env should not be added to Git');
        } catch (err) {
            expect(err.message).toContain('fatal: pathspec');
        }
    });

    test('Validate .env.example Structure', () => {
        const envExampleContent = fs.readFileSync('backend/.env.example', 'utf8');
        const requiredPlaceholders = ['APP_KEY=', 'DB_PASSWORD=', 'GOOGLE_CLIENT_ID=', 'GOOGLE_CLIENT_SECRET='];

        requiredPlaceholders.forEach(placeholder => {
            expect(envExampleContent).toContain(placeholder);
        });
    });

    test('Check for Hardcoded Secrets in Files', () => {
        const searchTerms = ['APP_KEY=', 'DB_PASSWORD='];

        searchTerms.forEach(term => {
            const grepResult = execSync(`grep -r "${term}" . || true`).toString();
            expect(grepResult).not.toContain(term);
        });
    });

    test('Verify Git History for Exposed Secrets', () => {
        const historyCheckCommands = [
            'git log --all --full-history -- "**/.env"',
            'git log --all -S"APP_KEY="'
        ];

        historyCheckCommands.forEach(command => {
            const historyResult = execSync(`${command} || true`).toString();
            expect(historyResult).not.toContain('APP_KEY=');
        });
    });

    test('Validate Laravel Configuration', () => {
        const envContent = fs.readFileSync('backend/.env', 'utf8');
        const placeholders = ['APP_KEY=', 'DB_PASSWORD='];

        placeholders.forEach(placeholder => {
            expect(envContent).toContain(placeholder);
        });
    });
});