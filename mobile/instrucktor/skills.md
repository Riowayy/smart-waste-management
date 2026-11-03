# SKILLS CONFIGURATION
## EcoTrack Smart Waste Management

## 1. Purpose

This document defines the mandatory skill usage policy for Codex during EcoTrack development.

Skills are used to improve:
- Code quality.
- Development efficiency.
- Project understanding.
- Planning accuracy.
- Bug investigation.
- Architecture consistency.
- Testing reliability.

Codex must use relevant skills before performing development tasks.

## 2. Local Skills Directory

The user's local skills directory is:

`C:\Users\Nazriel Fahri\.agents\skills`

This directory is the primary source of available development skills.

Before starting a task:

1. Inspect the available skill directories.
2. Locate the corresponding `SKILL.md` files.
3. Read the skill instructions.
4. Understand when and how each skill should be applied.
5. Apply the relevant instructions during implementation.

Do not assume a skill exists without checking its actual directory.

## 3. Mandatory Skills

The following three skills are mandatory for every code-changing task.

### A. lean-code

Purpose:

Implement clean, efficient, maintainable, and lightweight code.

Mandatory principles:

- Avoid unnecessary abstractions.
- Avoid duplicated code.
- Reuse existing components.
- Minimize unnecessary dependencies.
- Avoid excessive file creation.
- Keep functions focused.
- Keep components manageable.
- Remove genuinely unused imports and variables.
- Preserve existing working functionality.
- Prefer simple solutions over unnecessarily complex implementations.

Application:

Use this skill throughout implementation and code review.

### B. planning

Purpose:

Ensure changes are properly analyzed and planned before implementation.

Mandatory principles:

- Understand the requested feature.
- Identify the existing implementation.
- Identify affected files.
- Identify dependencies.
- Identify potential risks.
- Define implementation steps.
- Define testing requirements.
- Avoid unrelated modifications.

Application:

Use this skill before modifying source code.

For larger tasks, produce a more detailed implementation plan.

For small tasks, use a concise plan.

### C. project context

Purpose:

Maintain awareness of the entire EcoTrack project and prevent changes that conflict with existing architecture.

Mandatory principles:

- Understand the Laravel backend.
- Understand the Expo mobile architecture.
- Preserve the monorepo structure.
- Respect existing API contracts.
- Maintain established technology decisions.
- Avoid modifying unrelated website functionality.
- Consider dependencies between mobile and backend.
- Maintain consistency with existing project documentation.

Application:

Use this skill before making architectural decisions, implementing features, or changing existing code.

## 4. Mandatory Skill Execution Workflow

Every development task must follow this sequence:

```text
USER REQUEST
     |
     v
READ PROJECT INSTRUCTIONS
     |
     v
LOAD PROJECT CONTEXT SKILL
     |
     v
LOAD PLANNING SKILL
     |
     v
ANALYZE EXISTING IMPLEMENTATION
     |
     v
LOAD LEAN-CODE SKILL
     |
     v
IMPLEMENT CHANGES
     |
     v
TEST AND REVIEW
     |
     v
REPORT RESULTS
```

All three mandatory skills must be considered and applied.

Reading a skill file without applying its relevant instructions does not count as using that skill.

## 5. Additional Skill Selection

Codex may use additional skills depending on the requested task.

Additional skills are encouraged when they provide a meaningful improvement.

Examples:

| Task | Recommended Additional Skill |
|---|---|
| UI development | Frontend design / UI UX |
| React Native development | React Native development |
| API integration | API development |
| Authentication | Authentication and security |
| Laravel development | Laravel development |
| Database changes | Database design |
| Bug investigation | Debugging |
| Performance optimization | Performance engineering |
| Browser testing | Playwright |
| Automated testing | Testing and QA |
| Application architecture | Software architecture |
| EAS deployment | Expo and EAS deployment |

These are skill categories, not assumptions that corresponding local skills are installed.

Always inspect available skills before selecting them.

## 6. Online Skills

Online skills may be considered when:

- No suitable local skill exists.
- A more specialized skill is required.
- The task involves unfamiliar technology.
- A specialized testing workflow is needed.
- An additional skill provides substantial development value.

Before using an online skill:

1. Inspect its source.
2. Read its instructions.
3. Check its dependencies.
4. Evaluate its security.
5. Check compatibility with the current project.
6. Avoid executing untrusted scripts.
7. Avoid unnecessary installations.

Prefer existing local skills whenever they sufficiently cover the task.

Do not replace the three mandatory skills with online alternatives.

## 7. Playwright Skill and Browser Testing

Browser testing is encouraged when relevant.

Codex may use Playwright to:

- Open the running Expo Web application.
- Inspect application screens.
- Test navigation.
- Test forms.
- Test buttons and interactions.
- Detect browser console errors.
- Inspect network requests.
- Identify layout problems.
- Check responsive behavior.
- Reproduce reported UI bugs.

Before browser testing:

1. Confirm the application is running.
2. Confirm the browser testing environment is available.
3. Identify the target URL.
4. Define the test scenario.
5. Execute the test.
6. Record actual results.

Do not claim browser testing was completed if Playwright was not executed.

Native camera, GPS, and other device-specific capabilities must also be tested using an appropriate device or development build.

## 8. Expo Documentation Skill Selection

For Expo-related tasks, additional skills or documentation workflows should prioritize official Expo documentation.

Current project SDK:

Expo SDK 58.

Documentation:

`https://docs.expo.dev/versions/v58.0.0/`

Expo documentation index:

`https://docs.expo.dev/llms.txt`

Never rely exclusively on an older Expo skill or outdated documentation.

## 9. Skill Selection Rules

When selecting skills:

- Use the three mandatory skills first.
- Select additional skills based on the actual task.
- Avoid loading unrelated skills.
- Avoid duplicate skills with overlapping responsibilities.
- Follow the instructions of the selected skills.
- Maintain consistency with AGENTS.md and project documentation.

If skill instructions conflict with project security requirements or explicit user instructions, follow the higher-priority requirement and explain the conflict.

## 10. Skill Usage Reporting

For substantial tasks, include a short skill usage report:

```text
Skills Applied:

[Mandatory]
- lean-code
- planning
- project context

[Additional]
- Relevant skills selected for the task

Testing:
- ESLint
- TypeScript
- Expo Doctor
- Playwright (when applicable)
```

Only list skills that were actually inspected and applied.

## 11. Final Principle

Skills must improve actual development quality, not merely increase the number of instructions followed.

The objective is to make Codex:

- Understand before coding.
- Plan before changing.
- Write clean and efficient code.
- Respect existing architecture.
- Use appropriate tools.
- Test actual functionality.
- Avoid unnecessary modifications.

**Every development task must follow the three mandatory skills: lean-code, planning, and project context.**