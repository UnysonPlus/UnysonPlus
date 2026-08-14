(() => {
  // ../framework/static/js/controls/src/registry.js
  var controls = /* @__PURE__ */ new Map();
  function register(type, component) {
    if (typeof type !== "string" || !type) {
      throw new Error("fw.controls.register: type must be a non-empty string");
    }
    if (typeof component !== "function") {
      throw new Error(`fw.controls.register: component for "${type}" must be a function`);
    }
    controls.set(type, component);
  }
  function get(type) {
    return controls.get(type) || null;
  }
  function has(type) {
    return controls.has(type);
  }
  function types() {
    return Array.from(controls.keys()).sort();
  }

  // ../framework/static/js/controls/src/controls/text.jsx
  var { TextControl } = wp.components;
  function Text({ option = {}, value = "", onChange }) {
    return /* @__PURE__ */ wp.element.createElement(
      TextControl,
      {
        label: option.label || "",
        help: option.desc || void 0,
        value: value != null ? value : "",
        placeholder: option.attr && option.attr.placeholder || void 0,
        onChange,
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true
      }
    );
  }

  // ../framework/static/js/controls/src/controls/switch.jsx
  var { ToggleControl } = wp.components;
  var LEFT = { value: false, label: "No" };
  var RIGHT = { value: true, label: "Yes" };
  function Switch({ option = {}, value, onChange }) {
    const left = option["left-choice"] || LEFT;
    const right = option["right-choice"] || RIGHT;
    const same = (a, b) => JSON.stringify(a) === JSON.stringify(b);
    const checked = same(value, right.value);
    return /* @__PURE__ */ wp.element.createElement(
      ToggleControl,
      {
        label: option.label || "",
        help: option.desc || (checked ? right.label : left.label),
        checked,
        onChange: (next) => onChange(next ? right.value : left.value),
        __nextHasNoMarginBottom: true
      }
    );
  }

  // ../framework/static/js/controls/src/controls/select.jsx
  var { SelectControl } = wp.components;
  function Select({ option = {}, value, onChange }) {
    var _a;
    const choices = option.choices || {};
    const options = Object.keys(choices).map((key) => {
      const choice = choices[key];
      return {
        value: key,
        // A choice may be a plain label string, or a { text|label } object
        // (some option types decorate choices with extra metadata).
        label: typeof choice === "string" ? choice : choice && (choice.text || choice.label) || key
      };
    });
    return /* @__PURE__ */ wp.element.createElement(
      SelectControl,
      {
        label: option.label || "",
        help: option.desc || void 0,
        value: (_a = value != null ? value : option.value) != null ? _a : "",
        options,
        onChange,
        __next40pxDefaultSize: true,
        __nextHasNoMarginBottom: true
      }
    );
  }

  // ../framework/static/js/controls/src/controls/upload.jsx
  var { Button, BaseControl, Flex, FlexItem } = wp.components;
  function getMediaUpload() {
    if (wp.mediaUtils && wp.mediaUtils.MediaUpload) {
      return wp.mediaUtils.MediaUpload;
    }
    if (wp.blockEditor && wp.blockEditor.MediaUpload) {
      return wp.blockEditor.MediaUpload;
    }
    return null;
  }
  function toProtocolRelative(url) {
    return typeof url === "string" ? url.replace(/^https?:\/\//, "//") : "";
  }
  function toDisplayUrl(url) {
    return typeof url === "string" && url.startsWith("//") ? window.location.protocol + url : url;
  }
  function Upload({ option = {}, value, onChange }) {
    const MediaUpload = getMediaUpload();
    const current = value && value.url ? value : null;
    if (!MediaUpload) {
      return /* @__PURE__ */ wp.element.createElement(BaseControl, { label: option.label || "", __nextHasNoMarginBottom: true }, /* @__PURE__ */ wp.element.createElement("p", null, "The media library is not available on this screen."));
    }
    const onSelect = (media) => {
      onChange({
        attachment_id: media.id,
        url: toProtocolRelative(media.url)
      });
    };
    return /* @__PURE__ */ wp.element.createElement(
      BaseControl,
      {
        label: option.label || "",
        help: option.desc || void 0,
        __nextHasNoMarginBottom: true
      },
      current && /* @__PURE__ */ wp.element.createElement(
        "img",
        {
          src: toDisplayUrl(current.url),
          alt: "",
          style: {
            display: "block",
            maxWidth: "100%",
            height: "auto",
            marginBottom: "8px",
            borderRadius: "2px"
          }
        }
      ),
      /* @__PURE__ */ wp.element.createElement(
        MediaUpload,
        {
          onSelect,
          allowedTypes: option.images_only === false ? void 0 : ["image"],
          value: current ? current.attachment_id : void 0,
          render: ({ open }) => /* @__PURE__ */ wp.element.createElement(Flex, { justify: "flex-start", gap: 2 }, /* @__PURE__ */ wp.element.createElement(FlexItem, null, /* @__PURE__ */ wp.element.createElement(Button, { variant: "secondary", onClick: open }, current ? "Replace" : "Select image")), current && /* @__PURE__ */ wp.element.createElement(FlexItem, null, /* @__PURE__ */ wp.element.createElement(
            Button,
            {
              variant: "tertiary",
              isDestructive: true,
              onClick: () => onChange("")
            },
            "Remove"
          )))
        }
      )
    );
  }

  // ../framework/static/js/controls/src/index.jsx
  var { Notice } = wp.components;
  register("text", Text);
  register("switch", Switch);
  register("select", Select);
  register("short-select", Select);
  register("upload", Upload);
  function Undefined({ type }) {
    return /* @__PURE__ */ wp.element.createElement(Notice, { status: "warning", isDismissible: false }, `No React control for option type "${type}" yet \u2014 edit this option in the page builder.`);
  }
  function Option({ option, value, onChange }) {
    const type = option && option.type;
    if (!type) {
      return null;
    }
    const Control = get(type);
    if (!Control) {
      return /* @__PURE__ */ wp.element.createElement(Undefined, { type });
    }
    return /* @__PURE__ */ wp.element.createElement(Control, { option, value, onChange });
  }
  function Options({ options = {}, values = {}, onChange }) {
    return /* @__PURE__ */ wp.element.createElement(wp.element.Fragment, null, Object.keys(options).map((id) => /* @__PURE__ */ wp.element.createElement(
      Option,
      {
        key: id,
        option: options[id],
        value: values[id],
        onChange: (next) => onChange(id, next)
      }
    )));
  }
  window.fw = window.fw || {};
  window.fw.controls = { register, get, has, types, Option, Options };
})();
//# sourceMappingURL=fw-controls.js.map
