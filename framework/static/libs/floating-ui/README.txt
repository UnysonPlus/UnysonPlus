Floating UI
core 1.8.0 + dom 1.8.0 (UMD minified builds)
https://floating-ui.com/  MIT License

Vendored 2026-08-13 as the positioning engine for the framework tooltip
(framework/static/js/fw-tooltip.js, $.fn.fwTooltip).

Load order matters: core defines window.FloatingUICore, which the dom build requires.
