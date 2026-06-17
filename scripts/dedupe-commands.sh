#!/usr/bin/env bash
# Review then run: bash scripts/dedupe-commands.sh
set -e
cd "$(dirname "$0")/.."   # project root (script lives in scripts/)

# ── Problem 1: case-bug renames (live links 404 on Linux host) ──
git mv work/project-preview/project-preview-JACO-Growth-Trilogy.html work/project-preview/project-preview-jaco-growth-trilogy.html
git mv work/project-preview/project-preview-Ministry-of-Energy.html  work/project-preview/project-preview-ministry-of-energy.html
git mv work/project-preview/project-preview-SMO-Vision-2030.html      work/project-preview/project-preview-smo-vision-2030.html

# ── Problem 2: true duplicates (a USED lowercase twin already exists) ──
git rm work/project-preview/project-preview-AliExpress-GCC.html
git rm work/project-preview/project-preview-JACO-sport-conten.html
git rm work/project-preview/project-preview-JACO_commercial_systems.html
git rm work/project-preview/project-preview-Monthly_Champion.html
git rm work/project-preview/project-preview-Unknown_Soldier_League.html
git rm work/project-preview/project-preview-banan_exhibition.html
git rm work/project-preview/project-preview-ehsan_platform.html
git rm work/project-preview/project-preview-herfy_super_chicken.html
git rm work/project-preview/project-preview-ndmc.html
git rm work/project-preview/project-preview-riyadh_school_hittin.html
git rm work/project-preview/project-preview-ultimateGamer.html

# ── Problem 3: unlinked pair — keep kebab, drop snake_case ──
git rm work/project-preview/project-preview-ministry_of_culture.html
# (project-preview-ministry-of-culture.html is unlinked too — kept for now; no nav points to it)
