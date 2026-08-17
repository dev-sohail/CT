# ctlab-ui

Shared React component library for the CTLabs ecosystem, built on `@ctlab/ctlab-theme`.

## Components
- `Button` — `primary | secondary | danger | ghost`, sizes `sm | md | lg`
- `Input` — labelled input with error state
- `Card` — titled surface container
- `Modal` — accessible dialog (Esc / backdrop close)
- `Table<T>` — typed column/row data grid
- `ToastProvider` / `useToast` — transient notifications

## Usage
```tsx
import { Button, Card, ToastProvider } from '@ctlab/ctlab-ui';

<ToastProvider>
  <Card title="Welcome">
    <Button onClick={() => notify('Saved', 'success')}>Save</Button>
  </Card>
</ToastProvider>
```

## Design notes (Phase 3)

- **Tokens, not hardcoded colors.** All styling must reference CSS variables from `@ctlab/ctlab-theme` (`--ctlab-bg`, `--ctlab-surface`, `--ctlab-primary`, `--ctlab-border`, …). No hex values in components; theming happens in one place.
- **One control, many modules.** Components live in this package so every domain module (Planner, Wiki, Dashboard, future trackers) renders identically. Never fork a component into a module folder — extend it here and bump the version.
- **Keyboard + a11y first.** `Modal` closes on Esc/backdrop, focus is trapped, and `aria-*` is provided by the component, not the caller.
- **Typed data grids.** `Table<T>` takes columns as typed functions; module authors supply render cells, not raw DOM.
- **Wiring convention.** A module page = `Card` shell + typed `Table`/forms + `useToast` feedback; mutating calls go through `@ctlab/ctlab-api-client`, never raw `fetch`.
- **Roadmap.** Add `Select`, `DatePicker`, `Tag`/`TagInput` (pairing with the Tags kernel #8), `EmptyState`, and `Skeleton` next; then a themed `ctlab-widgets` gallery for the Dashboard (#22) widget registry.

