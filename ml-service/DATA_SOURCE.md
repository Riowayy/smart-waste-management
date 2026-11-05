# Dataset provenance and licensing

**Original dataset:** RealWaste — Sam Single, Saeid Iranmanesh, Raad Raad (2023).

- Author's repository: https://github.com/sam-single/realwaste
- UCI dataset: https://archive.ics.uci.edu/dataset/908/realwaste
- DOI: https://doi.org/10.24432/C5SS4G
- Original paper: *RealWaste: A Novel Real-Life Data Set for Landfill Waste Classification Using Deep Learning*.

**IMPORTANT LICENSE CONFLICT:** The author's GitHub README states **CC BY-NC-SA 4.0**, while UCI lists **CC BY 4.0**. These declarations are inconsistent. Until verified directly with the author, handle the downloaded images under the more restrictive CC BY-NC-SA 4.0 terms: attribution, non-commercial use, share alike. Do not claim the rights to use the original photographs for commercial purposes based only on this starter package.

The dataset builder selects and resizes photographs in the author's repository; it does **not** generate synthetic images. The generated `dataset/manifest.json` records the upstream revision, class mapping, original image URLs, and SHA-256 hashes.

## Label mapping
- RealWaste `Food Organics` → EcoTrack `Organic Waste` / `organic-waste`
- RealWaste `Paper` → EcoTrack `Paper Waste` / `paper-waste`
- RealWaste `Plastic` → EcoTrack `Plastic Waste` / `plastic-waste`

**Limitations:** RealWaste is captured at one landfill facility. Random image-level splits may include visually similar examples from the same collection environment. Good test accuracy on this held-out split does not guarantee accuracy on Indonesian smartphone images in different lighting and backgrounds. Validate with an independent set of locally captured waste photos when feasible.
