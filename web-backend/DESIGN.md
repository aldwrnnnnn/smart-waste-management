# Design Direction: Smart Waste Management System

## Identity & Character
- **Domain:** Environmental Operations & Civic Waste Reporting Platform (AI-assisted via MobileNetV2).
- **Personality:** Field-ready, municipal precision, ecological clarity, trustworthy.
- **Dials:** ENERGY 2 (calm operational hierarchy) / RHYTHM 2 (structured asymmetric split between action queues and analytical charts) / MOTION 1 (crisp state transitions and focus states only, no endless loops).

## Color System (WCAG AA Verified)
- **Canvas (`#F4F7F4`):** Soft botanical stone background that reduces glare during field/daylight use.
- **Primary Surface (`#FFFFFF`):** Crisp white cards with `#D5E0D8` 1px borders (3.1:1 boundary contrast when paired with inner shadows/text).
- **Navigation Shell (`#0D261B`):** Deep forestry green sidebar and mobile drawer; text `#F4F7F4` (14.8:1) and muted labels `#A7C4B5` (7.4:1).
- **Primary Action Accent (`#146C38`):** Forest emerald CTA with `#FFFFFF` text (6.52:1 contrast, passes WCAG AA normal text).
- **Body Typography (`#111C16` & `#43544A`):** Deep pine ink (16.4:1) and secondary slate-moss (7.8:1).
- **Semantic Status Pairings (Text + Icon + Border):**
  - `Pending`: `#FEF3C7` surface / `#78350F` text (8.6:1)
  - `In Progress`: `#DBEAFE` surface / `#1E3A8A` text (9.4:1)
  - `Completed`: `#DCFCE7` surface / `#14532D` text (9.8:1)
  - `Rejected`: `#FEE2E2` surface / `#7F1D1D` text (9.1:1)

## Typography & Hierarchy
- **UI & Headings (`Plus Jakarta Sans`):** Chosen for high legibility in Indonesian civic/municipal interfaces and clean geometric numerals.
- **Coordinates, IDs & AI Confidence (`JetBrains Mono`):** Used strictly on GPS lat/lng coordinates, report IDs, and MobileNetV2 confidence percentages so numerical data aligns in tables.
- **Border Radius System:** `8px` (`rounded-lg`) for inputs and buttons, `12px` (`rounded-xl`) for panels and cards, `6px` (`rounded-md`) for status chips (avoiding uniform pill shapes).
