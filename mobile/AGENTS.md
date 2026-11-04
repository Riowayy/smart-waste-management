# AGENTS.md - EcoTrack Mobile

## 1. Project Identity

This is the mobile application of **EcoTrack Smart Waste Management**.

Repository: `Riowayy/smart-waste-management`

Mobile directory: `mobile/`

Prioritize:
- Mobile-first development.
- Performance and lightweight implementation.
- Cross-platform compatibility.
- Maintainable architecture.
- Secure API integration.
- Consistent UI/UX.

The existing Laravel website is already developed. Do not modify the website unless explicitly requested.

## 2. Mandatory Project Context

Before starting any task:

1. Read the root `../AGENTS.md` if available.
2. Read all relevant documents inside `instrucktor/`.
3. Inspect the current source code before modifying it.
4. Check `package.json` and `app.json`.
5. Read `../docs/API.md` when working with backend integration.
6. Inspect the relevant Laravel routes, controllers, requests, models, and resources when necessary.
7. Create a short implementation plan before changing code.

Never assume that previous documentation perfectly represents the current source code.

## 3. Mandatory Skills

Setiap sesi dan setiap perubahan kode WAJIB menggunakan enam skill berikut:

1. `lean-code`
2. `planning`
3. `project-context`
4. `design-taste-frontend`
5. `emil-design-eng`
6. `impeccable`

Local skills directory:

`C:\Users\Nazriel Fahri\.agents\skills`

Instructions:

- Locate the corresponding skill directories and read their `SKILL.md` files.
- Apply the planning skill before implementing changes.
- Apply lean-code principles throughout implementation.
- Apply project context to preserve architecture, existing decisions, and project boundaries.
- Select additional relevant skills based on the actual task.
- If a required local skill is unavailable, report the issue rather than pretending it was used.
- Online skills are permitted when they provide a genuine improvement. Review their instructions and compatibility before using them.
- Do not install unrelated skills or dependencies unnecessarily.

## 4. Technology Stack Lock

The project uses:

- Expo SDK 58.
- React Native 0.88.1.
- React 19.3.0.
- TypeScript 6.0.3.
- Expo Router 58.
- Laravel REST API.
- MySQL.
- Laravel Sanctum.

These technologies must not be replaced without explicit approval.

Do not introduce:
- Flutter.
- Native Android development as the primary architecture.
- Next.js as a replacement for React Native.
- Another navigation framework.
- Another backend framework.

Existing dependency versions must be checked before introducing additional packages.

## 5. Expo Documentation Policy

**Expo changes between SDK releases. Never rely exclusively on training data.**

Before using Expo, EAS, or React Native APIs:

1. Read the Expo major version in `package.json`.
2. Access the matching versioned documentation.

For the current SDK 58:

`https://docs.expo.dev/versions/v58.0.0/`

3. Read:

`https://docs.expo.dev/llms.txt`

4. Follow the official documentation links relevant to the task.
5. Verify API compatibility before writing code.

Never use outdated APIs simply because they worked in an older Expo SDK.

## 6. Package Management

This repository currently uses npm.

Use:

```bash
npx expo install <package>
```

For Expo-compatible dependencies.

Available commands:

```bash
npm install
npx expo start
npx expo lint
npx tsc --noEmit
npx expo-doctor
npx expo install --check
npx expo install --fix
npm run web
```

Use `bunx` only if the project is intentionally migrated to Bun and contains the appropriate lockfile.

Important:
- Do not run `npm audit fix --force` without examining its consequences.
- Do not update all dependencies unnecessarily.
- Preserve Expo SDK compatibility.
- Avoid redundant packages.
- Prefer Expo-supported modules.

## 7. Navigation and Routing

Use Expo Router for all application navigation.

Routes are located in:

`src/app/`

Rules:
- Every route file represents a screen.
- `_layout.tsx` defines navigation layouts.
- Components, hooks, services, and utilities belong outside the route directory.
- Import navigation utilities from `expo-router`.
- Maintain typed routes where applicable.

Documentation:

`https://docs.expo.dev/router/introduction.md`

## 8. Application Architecture

Follow a modular architecture.

Responsibilities:

- `app/`: Routing and screens.
- `components/`: Reusable UI components.
- `features/`: Feature-specific logic.
- `services/`: API communication and external services.
- `constants/`: Theme and configuration.
- `hooks/`: Reusable React hooks.
- `types/`: Shared TypeScript definitions.

Avoid unnecessary abstractions and excessive file fragmentation.

## 9. Backend Integration

Laravel is the primary backend and source of truth.

API prefix:

`/api/v1`

Authentication:

Laravel Sanctum Bearer Token.

Rules:
- Never invent API endpoints.
- Verify endpoint contracts before implementation.
- Use the existing Laravel API.
- Respect server-side validation.
- Handle HTTP errors correctly.
- Do not modify Laravel backend files unless explicitly requested.
- Never implement fake AI responses.

Use environment configuration for API URLs.

Never expose private credentials through `EXPO_PUBLIC_*`.

Never store authentication tokens in AsyncStorage.

## 10. Security and Privacy

- Protect authentication tokens.
- Never log passwords or tokens.
- Respect camera and location permissions.
- Do not send GPS coordinates without appropriate user permission.
- Do not trust user-submitted role or ownership identifiers.
- Respect Laravel authorization and report ownership.
- Use HTTPS in production.
- Handle unauthorized sessions correctly.

## 11. UI/UX Standards

Design EcoTrack as a professional waste management application.

Requirements:
- Modern and clean interface.
- Consistent green visual identity.
- Responsive layouts.
- Mobile-first implementation.
- Proper safe area handling.
- Accessible typography and controls.
- Consistent spacing and colors.
- Clear loading, empty, success, and error states.

Use reusable components and centralized design tokens.

All user-facing application text must use Indonesian.

## 12. EAS Build Policy

Use Expo Application Services for cloud builds.

Documentation:

`https://docs.expo.dev/eas/index.md`

Commands:

```bash
npx eas-cli@latest build --platform android
```

Rules:
- Prefer cloud builds.
- Do not require Android Studio for routine development.
- Do not create native folders manually.
- Do not modify generated native projects manually.
- Configure native functionality through `app.json` and supported config plugins.
- Check EAS configuration before starting a production build.

## 13. Continuous Native Generation

If `android/` and `ios/` directories do not exist, Expo generates them when required.

Never create or edit them manually.

Use Expo configuration and config plugins.

Remember that native dependencies may require a development build instead of Expo Go.

## 14. Testing and Browser Inspection

Before declaring work complete:

Run:

```bash
npm run lint
npx tsc --noEmit
npx expo-doctor
```

When relevant, use Playwright for browser testing.

Playwright may be used to:
- Inspect the running Expo Web application.
- Navigate through screens.
- Test forms and buttons.
- Verify authentication flows.
- Detect UI errors.
- Identify browser console errors.
- Check responsive layouts.
- Reproduce application bugs.

Use existing Playwright tooling when available. Do not add unnecessary dependencies.

Do not claim a test passed unless it was actually executed.

## 15. File Management

Before deleting or replacing files:

1. Inspect the file.
2. Search for imports and references.
3. Check platform-specific implementations.
4. Check asset references.
5. Check app configuration.
6. Verify whether the file is still needed.

Never perform bulk deletion without dependency verification.

Preserve working Expo configuration.

## 16. Git Safety

- Never force push.
- Never delete branches.
- Never overwrite user changes.
- Never commit without permission.
- Never push without permission.
- Inspect Git status before modifying existing work.

## 17. Development Workflow

For every task, follow this workflow:

1. Understand the request.
2. Read relevant project context.
3. Read and apply the six mandatory skills.
4. Inspect the existing implementation.
5. Check official documentation when required.
6. Create an implementation plan.
7. Implement the smallest suitable change.
8. Run relevant checks.
9. Test the application.
10. Report changes and remaining issues.

## 18. Completion Report

Every completed task must include:

- What was implemented.
- Files created or modified.
- Dependencies added or changed.
- Tests executed.
- Actual test results.
- Known limitations.
- Recommended next step.

Never report untested functionality as completed.

**Primary objective: Build a lightweight, reliable, secure, and professional EcoTrack mobile application without disrupting the existing Laravel website and backend architecture.**

## Kebijakan enam skill wajib (TASK 004)

Pada setiap sesi dan setiap perubahan kode, wajib baca dan terapkan keenam skill berikut dari C:/Users/Nazriel Fahri/.agents/skills: lean-code, planning, project-context, design-taste-frontend, emil-design-eng, impeccable. Verifikasi nama folder dan SKILL.md sebenarnya; laporkan jika tidak ditemukan. Terapkan bagian relevan untuk tugas, jangan hanya menyebut nama. Skill tambahan diperbolehkan bila relevan. Instruksi eksplisit pengguna mengungguli workflow skill, termasuk larangan screenshot. Aturan ini melengkapi daftar dan contoh workflow lama di dokumen ini.
