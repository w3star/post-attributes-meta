(function (wp) {
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editPost;
    const { useSelect, useDispatch } = wp.data;
    const { __ } = wp.i18n;
    const { PanelRow, RangeControl, SelectControl, TextControl } = wp.components;
    const { createElement: h, Fragment } = wp.element;

    // Konfiguration kommt aus PHP via wp_localize_script -> PAM_SECTIONS (Array)
    const SECTIONS = Array.isArray(window.PAM_SECTIONS) ? window.PAM_SECTIONS : [];

    function FieldControl({ field, meta, editMeta }) {
        const key = field.key;
        const val = meta[key];

        // einfache Normalisierer
        const toInt = (v) => {
            const n = parseInt(v, 10);
            return isNaN(n) ? 0 : n;
        };

        switch (field.type) {
            case 'int':
                return h(PanelRow, {},
                    h(RangeControl, {
                        label: field.label || key,
                        value: toInt(val),
                        min: field.min ?? 0,
                        max: field.max ?? 100,
                        step: field.step ?? 1,
                        onChange: (next) => editMeta({ [key]: toInt(next) }),
                    })
                );

            case 'select':
                return h(PanelRow, {},
                    h(SelectControl, {
                        label: field.label || key,
                        value: (val ?? ''),
                        options: (field.options || []).map(o => ({ label: o.label, value: o.value })),
                        onChange: (next) => editMeta({ [key]: next }),
                    })
                );

            case 'text':
                return h(PanelRow, {},
                    h(TextControl, {
                        label: field.label || key,
                        value: val ?? '',
                        onChange: (next) => editMeta({ [key]: next }),
                    })
                );

            default:
                return h('div', { style: { opacity: .7, fontStyle: 'italic' } }, `Unbekannter Feldtyp: ${field.type}`);
        }
    }

    function SectionPanel({ section }) {
        const meta = useSelect((select) => select('core/editor').getEditedPostAttribute('meta') || {}, []);
        const { editPost } = useDispatch('core/editor');
        const editMeta = (patch) => editPost({ meta: { ...meta, ...patch } });

        return h(PluginDocumentSettingPanel, { name: section.id, title: section.title, className: 'pam-section' },
            h(Fragment, {},
                (section.fields || []).map(f =>
                    h(FieldControl, { key: f.key, field: f, meta, editMeta })
                )
            )
        );
    }

    function PAMSidebar() {
        if (!SECTIONS.length) {
            return h('div', { style: { padding: '8px', opacity: .7 } }, 'Keine PAM_SECTIONS definiert.');
        }
        return h(Fragment, {}, SECTIONS.map(s => h(SectionPanel, { key: s.id, section: s })));
    }

    registerPlugin('pam-sidebar-panels', { render: PAMSidebar, icon: null });
})(window.wp);