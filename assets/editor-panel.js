
( function( wp ) {
  const { registerPlugin } = wp.plugins;
  const { PluginDocumentSettingPanel } = wp.editPost;
  const { useSelect } = wp.data;
  const { SelectControl, __experimentalNumberControl: NumberControl, TextControl } = wp.components;
  const { createElement: el, useCallback } = wp.element;

  function Panel() {
    const postType = wp.data.select('core/editor').getCurrentPostType();
    const meta = useSelect( s => s('core/editor').getEditedPostAttribute('meta') || {}, [] );
    const editPost = wp.data.dispatch('core/editor').editPost;
    const savePost = wp.data.dispatch('core/editor').savePost;

    if ( postType !== 'post' && postType !== 'page' ) return null;

    const setMeta = useCallback((key, value) => {
      const next = { ...meta, [key]: value };
      editPost({ meta: next });
    }, [meta]);

    function handleDifficulty(value){
      setMeta('pam_difficulty', value);
    }

    return el( PluginDocumentSettingPanel, { name:'pam-panel', title:'Zusatzinfos', className:'pam-panel' },
      el( NumberControl, {
        label: 'Rating (0–5)', min:0, max:5, step:1,
        value: meta.pam_rating || 0,
        onChange: v => setMeta('pam_rating', parseInt(v||0,10))
      } ),
      el( SelectControl, {
        label: 'Schwierigkeit',
        value: meta.pam_difficulty || '',
        options: [
          { label: '—', value: '' },
          { label: 'Leicht', value: 'easy' },
          { label: 'Mittel', value: 'medium' },
          { label: 'Schwer', value: 'hard' },
        ],
        onChange: handleDifficulty,
        __nextHasNoMarginBottom: true
      } ),
      el( NumberControl, {
        label: 'Schönheit (0–5)', min:0, max:5, step:1,
        value: meta.pam_beauty || 0,
        onChange: v => setMeta('pam_beauty', parseInt(v||0,10))
      } ),
      el( NumberControl, {
        label: 'Dauer (Minuten)', min:0, step:10,
        value: meta.pam_duration_min || 0,
        onChange: v => setMeta('pam_duration_min', parseInt(v||0,10))
      } )
    );
  }

  registerPlugin( 'pam-panel', { render: Panel, icon: 'info' } );
} )( window.wp );
