---
name: tailwindcss-development
description: >-
  Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components,
  working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors,
  typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle,
  hero section, cards, buttons, or any visual/UI changes.
---

### 🎨 **Tailwind CSS v4 Core**

<tailwind>
- **CSS-First:** Configuration via `@theme` in CSS. No `tailwind.config.js`.
- **Import:** Use `@import "tailwindcss";`.
- **Utilities:** Numeric opacity (e.g., `bg-black/50`), no `bg-opacity-*`.
</tailwind>

### 📐 **Layout & Patterns**

<layout>
- **Grid/Flex:** Prefer `gap` utilities over margins for siblings.
- **Responsive:** Mobile-first design with `md:`, `lg:`, etc.
- **Dark Mode:** Support `dark:` variants for all components.
</layout>

### 💎 **UI Best Practices**

<ui>
- **Extract Components:** Extract repeated patterns into Blade/Livewire components.
- **Clean HTML:** Group parent/child classes logically to reduce repetition.
</ui>