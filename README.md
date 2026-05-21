# [Unyson+](https://github.com/jonmlas/Unyson+) Framework

Unyson+ is a **community-maintained fork** of the original [Unyson](https://wordpress.org/plugins/unyson/) framework for [WordPress](http://wordpress.org/).  

This project continues where the original Unyson (by [ThemeFuse](http://themefuse.com/)) left off after development was discontinued, with the goal of keeping the framework alive, stable, and more developer-friendly.

---

## 🔹 Key Differences from the Original Unyson
* Removed Brizy and all references to it.
* Updated to PHP 8.x compatible. Dropped support for PHP 5.6/7.0/7.1/7.2/7.3.
* Ongoing shortcode upgrades.

## 🔹 Updates Done
* Addressed Security Issues with the help of AI agents.
  - Missing Authorization & Access Violation (CVE-2023-44472): A massive flaw where the plugin failed to properly validate user permissions. Unauthenticated users or low-level accounts (like basic subscribers) could access restricted backend functions, modify data, and trigger administrative actions they shouldn't have access to.
  - Cross-Site Request Forgery (CSRF - CVE-2024-34814): Missing or incorrect nonce validation allowed attackers to trick logged-in administrators into executing unwanted, malicious actions on the site via simple phishing or malicious links.
  - Reflected Cross-Site Scripting (XSS - CVE-2022-2219): Insufficient input sanitization and output escaping in its extension-handling code allowed malicious scripts to be injected and executed directly in an admin's browser session.
* **Full PHP 8.x+ support**: update and refactor functions (`create_function`, `each`, curly braces `{}`, `implode()` argument order, etc.).
* Upgrade from **Bootstrap 3 → Bootstrap 5**.
*  **Modernize the codebase**:
  - Remove deprecated PHP patterns.
  - Add strict typing where possible.
  - Ensure better code organization and readability.
* **Backward compatibility**: maintain support for legacy themes and shortcodes while adding modern features.
* **Upgrade dependencies**:
  - jQuery usage cleanup.
  - Keep Bootstrap updated to the latest stable version.

---

## 🔹 Plans for This Project
* **Ongoing Shortcode improvements**: add more features, flexibility, and developer-friendly APIs.
* **Gutenberg integration**: improve compatibility with the Block Editor while keeping Classic Editor support.
* **Options Framework improvements**: make the admin options system more developer-friendly (inspired by ACF / Carbon Fields).
* **Migration tools**: help existing Unyson users transition smoothly to Unyson+ without breaking sites.
* **Automated testing**: introduce PHPUnit + WordPress test suite for long-term stability.
* **Changelog and releases**: maintain semantic versioning (e.g., v1.1.0, v1.2.0).
* **Extension registry**: create an open system for community-driven add-ons and modules.
* **Multisite support**: ensure full compatibility in multisite environments.

---

## Table of Contents

* [Installation](#installation)
* [Documentation](#documentation)
* [Extensions](#extensions)
* [Contributing](#contributing)
* [Bug Reports](#bug-reports)
* [License](#license)

## Installation

1. Download or clone the repository into your WordPress `plugins` directory.
2. Activate **Unyson+** from the WordPress dashboard under **Plugins**.
3. Configure the framework by going to the **Unyson+ menu**.

⚠ Warning: If you currently have the original Unyson plugin installed, create a staging site or backup your site first. Unyson+ shares the same function names as Unyson, so running both at the same time will cause fatal errors. Uninstall Unyson before installing Unyson+.

## Documentation

Currently, the original Unyson url (http://manual.unyson.io/) documentation has been taken down.  Historical Unyson documentation can still be accessed via the Internet Archive:
👉 [Archived Unyson Documentation Manual](https://web.archive.org/web/20221130053349/http://manual.unyson.io/)


Future Unyson+-specific documentation will be published here in the repository’s **/docs** folder. Contributions are welcome.

## Extensions

Unyson+ supports the same modular extension system as the original Unyson. Extensions can be enabled/disabled as needed.  
We aim to gradually improve, fix, and modernize these extensions.  

Examples include:

- Page Builder  
- Shortcodes  
- Mega Menu  
- Sidebars  
- Sliders  
- Portfolio  
- Backup & Demo Content  
- SEO  
- Forms  
- Feedback  
- Breadcrumbs  
- Events  
- Analytics  
- Mailer  
- Social  
- Blog Posts  
- Translation  

👉 Over time, Unyson+ will host updated and modernized versions of these extensions here under separate repositories for easier maintenance.

## Contributing

You can help keep Unyson+ alive and growing!  

Ways to contribute:
- **Code contributions** via Pull Requests
- **Documentation improvements**
- **Bug fixes** and reporting
- **New or modernized extensions**

Contributor guidelines will be published soon in `CONTRIBUTING.md`.

## Bug Reports

Please submit issues here:  
👉 [Unyson+ Issues](https://github.com/UnysonPlus/UnysonPlus/issues)

When reporting a bug:
- Provide detailed steps to reproduce the issue
- Include WordPress version, PHP version, and theme/plugin details
- Share error logs or screenshots if possible

## License

Unyson+ is released under the [GPL-3.0 License](https://github.com/jonmlas/UnysonPlus/blob/master/framework/LICENSE).  

Original code copyright © 2014 ThemeFuse LTD.  
Fork maintained and extended by the Unyson+ community.  

---
> ⚡ *Unyson+ — carrying on the legacy of Unyson for modern WordPress development.*

