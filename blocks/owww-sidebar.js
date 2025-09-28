/* blocks/owww-sidebar.js */
(function (wp) {
    // Harte Guards + Debug
    if (!wp) { console.error('[OWWW] window.wp fehlt'); return; }
    if (!wp.plugins || !wp.editPost || !wp.data || !wp.components || !wp.element) {
        console.error('[OWWW] Gutenberg Packages fehlen', { hasPlugins: !!wp.plugins, hasEditPost: !!wp.editPost });
        return;
    }

    const { registerPlugin } = wp.plugins;
    //const { PluginDocumentSettingPanel } = wp.editPost;
    const { PluginDocumentSettingPanel } = wp.editor || wp.editPost;
    const { useSelect, useDispatch } = wp.data;
    const { PanelRow, RangeControl, SelectControl, TextControl, Notice } = wp.components;
    const { createElement: h, Fragment, useMemo } = wp.element;

    const SECTIONS = Array.isArray(window.OWWW_SECTIONS) ? window.OWWW_SECTIONS : [];
    console.log('[OWWW] owww-sidebar.js boot', { sections: SECTIONS });

    // Hilfs-Funktionen
    const toInt = (v) => {
        const n = parseInt(v, 10);
        return isNaN(n) ? 0 : n;
    };

    function FieldControl({ field, meta, editMeta }) {
  const key = field.key;
  const raw = meta[key];
  const type = field.type || 'text';            // 'int' | 'text'
  const widget = field.widget || (type === 'int' ? 'range' : 'input'); // 'select' | 'range' | 'input'
  const toInt = (v) => {
    const n = parseInt(v, 10);
    return isNaN(n) ? 0 : n;
  };

  // ---- SELECT (immer Strings) ----
  if (widget === 'select') {
    const opts = (field.options || []).map(o => ({ label: o.label, value: String(o.value) }));
    const val = (raw ?? '');
    return wp.element.createElement(
      wp.components.PanelRow,
      null,
      wp.element.createElement(wp.components.SelectControl, {
        label: field.label || key,
        value: String(val),
        options: opts,
        onChange: (next) => editMeta({ [key]: String(next) }),
      })
    );
  }

  // ---- NUMERIC INPUT (kompakt, Label + Feld nebeneinander) ----
  if (widget === 'input' && type === 'int') {
    const NumberControl = wp.components.__experimentalNumberControl;
    const Control = NumberControl || wp.components.TextControl;
    const min = field.min ?? 0, max = field.max ?? 999999, step = field.step ?? 1;
    return wp.element.createElement(
      wp.components.PanelRow,
      null,
      wp.element.createElement('span', { className: 'owww-fieldlabel' }, field.label || key),
      wp.element.createElement(Control, {
        label: undefined,
        value: raw ?? '',
        onChange: (next) => editMeta({ [key]: toInt(next) }),
        ...(NumberControl ? { min, max, step } : { inputMode: 'numeric', pattern: '[0-9]*' }),
        className: 'owww-number-inline'
      })
    );
  }

  // ---- RANGE SLIDER (Fallback für int) ----
  if (widget === 'range' && type === 'int') {
    const min = field.min ?? 0, max = field.max ?? 100, step = field.step ?? 1;
    return wp.element.createElement(
      wp.components.PanelRow,
      null,
      wp.element.createElement(wp.components.RangeControl, {
        label: field.label || key,
        value: toInt(raw),
        min, max, step,
        onChange: (next) => editMeta({ [key]: toInt(next) }),
      })
    );
  }

  // ---- TEXT (Fallback) ----
  return wp.element.createElement(
    wp.components.PanelRow,
    null,
    wp.element.createElement(wp.components.TextControl, {
      label: field.label || key,
      value: raw ?? '',
      onChange: (next) => editMeta({ [key]: next })
    })
  );
}


    function SectionPanel({ section }) {
        const meta = useSelect((select) => select('core/editor').getEditedPostAttribute('meta') || {}, []);
        const { editPost } = useDispatch('core/editor');
        const editMeta = (patch) => editPost({ meta: { ...meta, ...patch } });

        const fields = section.fields || [];
        return h(PluginDocumentSettingPanel, { name: section.id, title: section.title, className: 'owww-section' },
            h(Fragment, {},
                fields.length
                    ? fields.map(f => h(FieldControl, { key: f.key, field: f, meta, editMeta }))
                    : h(Notice, { status: 'info', isDismissible: false }, 'Keine Felder konfiguriert.')
            )
        );
    }

    function OWWWSidebar() {
        const postType = useSelect((s) => s('core/editor').getCurrentPostType(), []);
        const meta = useSelect((s) => s('core/editor').getEditedPostAttribute('meta') || {}, []);
        console.log('[OWWW] sidebar render', { postType, meta });

        // Falls keine Sections geliefert wurden, zeige ein klar sichtbares Testpanel
        if (!SECTIONS.length) {
            return h(PluginDocumentSettingPanel, { name: 'owww_test', title: 'OWWW Testpanel' },
                h('div', null,
                    'OWWW_SECTIONS ist leer oder nicht gesetzt. ',
                    'Prüfe wp_localize_script() → Handle "owww-sidebar".'
                )
            );
        }

        return h(Fragment, {}, SECTIONS.map(s => h(SectionPanel, { key: s.id, section: s })));
    }

    registerPlugin('owww-sidebar-panels', { render: OWWWSidebar, icon: null });
})(window.wp);
