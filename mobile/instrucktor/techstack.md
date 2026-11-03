# TECH STACK DOCUMENTATION
## EcoTrack Smart Waste Management

**Repository:** Riowayy/smart-waste-management
**Mobile Directory:** mobile/
**Architecture:** Monorepo (Laravel Backend + React Native Mobile)

---

# 1. TECHNOLOGY POLICY

This document defines the official technology stack for EcoTrack.

All development agents must follow these technology decisions.

IMPORTANT:
- Do not replace existing frameworks.
- Do not upgrade dependencies without justification.
- Do not introduce alternative architectures.
- Do not migrate to another programming language.
- Do not remove existing technologies without approval.

Any major technology change requires explicit user approval.

# 2. BACKEND TECHNOLOGY

| Component | Technology |
|---|---|
| Backend Framework | Laravel |
| Programming Language | PHP |
| Database | MySQL |
| API Architecture | REST API |
| Authentication | Laravel Sanctum |
| Backend Rendering | Blade |
| Frontend Styling | CSS |
| Frontend Scripting | JavaScript |

Backend responsibilities:

- User authentication.
- User management.
- Waste category management.
- Waste report management.
- Report status management.
- Database operations.
- API responses.
- Authorization and validation.

The Laravel backend is the primary source of truth.

Do not replace Laravel with another backend framework.

Exact Laravel and PHP versions must be verified against `composer.json` and the active runtime configuration before making backend changes.

# 3. MOBILE TECHNOLOGY

## Core Framework

| Component | Technology | Version |
|---|---|---|
| Mobile Framework | React Native | 0.88.1 |
| Development Framework | Expo | SDK 58 |
| Expo Package | expo | ~58.0.22 |
| Programming Language | TypeScript | ~6.0.3 |
| React | React | 19.3.0 |
| React DOM | React DOM | 19.3.0 |
| Navigation | Expo Router | ~58.0.29 |

These versions reflect the package.json baseline inspected on November 3, 2026.

Do not upgrade or downgrade them without checking compatibility and obtaining approval.

## Development Environment

| Component | Technology |
|---|---|
| IDE | Visual Studio Code |
| Runtime | Node.js |
| Package Manager | npm |
| Preview | Expo Web |
| Browser Testing | Playwright |
| Android Build | EAS Build |
| Version Control | Git |
| Repository Hosting | GitHub |

Developer environment baseline:
- Node.js 24.22.1
- npm 11.19.1

Android Studio is not part of the required development workflow.

The development machine has limited RAM. Prefer lightweight development processes.

# 4. INSTALLED EXPO DEPENDENCIES

The following dependencies were present in package.json during the initial inspection.

| Package | Version |
|---|---|
| @expo/ui | ~58.0.26 |
| expo-constants | ~58.0.14 |
| expo-device | ~58.0.5 |
| expo-font | ~58.0.11 |
| expo-glass-effect | ~58.0.5 |
| expo-image | ~58.0.18 |
| expo-linking | ~58.0.15 |
| expo-splash-screen | ~58.0.12 |
| expo-status-bar | ~58.0.4 |
| expo-symbols | ~58.0.4 |
| expo-system-ui | ~58.0.7 |
| expo-web-browser | ~58.0.7 |

# 5. INSTALLED REACT NATIVE DEPENDENCIES

| Package | Version |
|---|---|
| react-native-gesture-handler | ~3.3.0 |
| react-native-reanimated | 4.7.1 |
| react-native-safe-area-context | ~5.10.1 |
| react-native-screens | ~4.30.1 |
| react-native-web | ~0.21.4 |
| react-native-worklets | 0.13.0 |

Do not remove existing dependencies until their usage and compatibility have been checked.

# 6. TYPESCRIPT CONFIGURATION

TypeScript is the primary language for mobile development.

Configuration requirements:

- Strict mode must remain enabled.
- Use explicit types for API responses.
- Avoid unnecessary `any`.
- Maintain type safety between services and screens.
- Preserve the existing path aliases.

Existing alias:

`@/*` -> `src/*`

Configuration file:

`mobile/tsconfig.json`

Do not disable strict mode to bypass TypeScript errors.

# 7. NAVIGATION

Official navigation framework:

**Expo Router**

All application routes must use file-based routing.

Routes:

`mobile/src/app/`

Rules:
- Use `_layout.tsx` for navigation layouts.
- Keep reusable components outside the app directory.
- Avoid installing another navigation framework.
- Maintain compatibility with Expo SDK 58.

# 8. STYLING AND UI

Primary UI technology:

- React Native StyleSheet.
- React Native components.
- Expo-supported UI modules where appropriate.
- Centralized theme and design tokens.

Design direction:

- Modern.
- Professional.
- Minimalist.
- Mobile-first.
- Consistent visual identity.
- Emerald/green EcoTrack branding.

Reference brand color:

`#006c49`

Do not introduce another UI framework without evaluating its necessity and Expo compatibility.

# 9. API COMMUNICATION

Backend communication:

REST API using HTTP/HTTPS.

Base API prefix:

`/api/v1`

Authentication:

Laravel Sanctum Bearer Token.

API URL must be configurable through environment variables.

Example:

`EXPO_PUBLIC_API_URL`

Never hardcode production API URLs throughout the application.

Do not expose private server credentials through public Expo environment variables.

# 10. STORAGE AND DEVICE CAPABILITIES

The following capabilities are planned for mobile development:

| Capability | Technology Direction | Status |
|---|---|---|
| Secure Token Storage | Expo SecureStore | Not installed |
| Image Selection | Expo ImagePicker | Not installed |
| Camera | Expo Camera | Not installed |
| GPS Location | Expo Location | Not installed |
| Image Display | Expo Image | Installed |
| Local Data Persistence | Evaluate per feature | Not decided |

IMPORTANT:

These packages are planned technologies, not currently installed dependencies.

Before installation:
1. Check Expo SDK 58 compatibility.
2. Read official documentation.
3. Use `npx expo install` for Expo-compatible packages.
4. Check whether native development builds are required.
5. Avoid unnecessary dependencies.

# 11. AI IMAGE DETECTION

AI image detection is a planned EcoTrack feature.

Current backend schema includes:
- ai_category_id
- ai_confidence

However, an operational mobile AI prediction endpoint has not been verified.

Technology decisions:

- Laravel remains the API gateway where applicable.
- AI inference must use a verified backend or ML service.
- React Native handles image selection and presentation of actual prediction results.
- AI responses must follow a documented API contract.

Do not introduce TensorFlow, PyTorch, ONNX, or another ML framework into mobile without an explicit architectural decision.

Do not generate fake AI predictions.

# 12. BUILD AND DEPLOYMENT

Official build system:

**Expo Application Services (EAS)**

Android builds should use cloud infrastructure.

Preferred command:

`npx eas-cli@latest build --platform android`

Development preview:

`npm run web`

Development server:

`npx expo start`

Android Studio is not required for routine cloud builds.

# 13. TESTING TECHNOLOGY

Testing tools:

| Tool | Purpose |
|---|---|
| ESLint | Code quality |
| TypeScript Compiler | Type checking |
| Expo Doctor | Dependency and configuration checks |
| Playwright | Browser automation |
| Expo Web | Browser-based UI preview |

Playwright should be used when relevant and available.

Do not install another testing framework without identifying a real testing requirement.

# 14. PACKAGE MANAGEMENT RULES

Current package manager:

**npm**

Lockfile:

`mobile/package-lock.json`

Rules:

- Preserve package-lock.json.
- Use Expo-compatible package installation.
- Avoid unnecessary dependency upgrades.
- Do not use `npm audit fix --force` without reviewing changes.
- Do not install duplicate libraries.
- Do not replace npm with another package manager without approval.

For Expo-compatible dependencies:

`npx expo install <package>`

# 15. FORBIDDEN ARCHITECTURAL CHANGES

The following changes are prohibited without explicit user approval:

1. Replacing React Native with Flutter.
2. Replacing Expo with another mobile framework.
3. Replacing Expo Router.
4. Replacing Laravel backend.
5. Replacing MySQL.
6. Replacing TypeScript with JavaScript.
7. Introducing another backend architecture.
8. Migrating the repository into separate repositories.
9. Upgrading Expo SDK without a migration plan.
10. Replacing the existing API authentication mechanism.

# 16. FINAL TECHNOLOGY PRINCIPLE

Maintain the existing technology stack.

Prefer compatibility over novelty.

Prefer lightweight dependencies over unnecessary libraries.

Prefer official Expo modules when appropriate.

Preserve the monorepo architecture.

All technical decisions must support the primary objective:

**Deliver a stable, lightweight, secure, and production-ready EcoTrack Mobile application integrated with the existing Laravel backend.**