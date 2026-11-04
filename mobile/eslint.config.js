// https://docs.expo.dev/guides/using-eslint/
const { defineConfig } = require('eslint/config');
const expoConfig = require('eslint-config-expo/flat');

module.exports = defineConfig([
  expoConfig,
  {
    files: ['tests/Unit/api-foundation.test.cjs'],
    languageOptions: { globals: { __dirname: 'readonly' } },
  },
  {
    files: ['tests/Unit/reporting-http.cjs'],
    languageOptions: { globals: { Buffer: 'readonly' } },
  },
  {
    ignores: ['dist/*'],
  },
]);
