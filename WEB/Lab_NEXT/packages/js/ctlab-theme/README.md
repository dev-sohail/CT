# ctlab-theme

Design tokens and theme system for the CTLabs ecosystem.

- `tokens.ts` — color/radius/spacing tokens (HSL channel variables consumable by Tailwind)
- `globals.css` — CSS variables for `dark` (default) and `light` via `[data-ctlab-theme]`
- `ThemeProvider` / `useTheme` — React context to switch themes

## Usage
```tsx
import { ThemeProvider } from '@ctlab/ctlab-theme';
import '@ctlab/ctlab-theme/globals.css';

<ThemeProvider defaultMode="dark">{children}</ThemeProvider>
```
