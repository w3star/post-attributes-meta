
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
      setMeta('owww_difficulty_hiking', value);
    }

    return el( PluginDocumentSettingPanel, { name:'owww-panel', title:'Zusatzinfos', className:'owww-panel' },
      el( NumberControl, {
        label: 'Rating (0–5)', min:0, max:5, step:1,
        value: meta.owww_rating || 0,
        onChange: v => setMeta('owww_rating', parseInt(v||0,10))
      } ),
      el( SelectControl, {
        label: 'Schwierigkeit',
        value: meta.owww_difficulty_hiking || '',
        options: [
          { label: '—', value: '' },
          { label: 'Wandern', value: 'T1' },
          { label: 'Bergwandern', value: 'T2' },
          { label: 'anspruchsvolles Bergwandern', value: 'T3' },
          { label: 'Alpinwandern', value: 'T4' },
          { label: 'anspruchsvolles Alpinwandern', value: 'T5' },
          { label: 'schwierieges Alpinwandern', value: 'T6' },
        ],
        onChange: handleDifficulty,
        __nextHasNoMarginBottom: true
      } ),
      el( NumberControl, {
        label: 'Exklusivität (0–5)', min:0, max:5, step:1,
        value: meta.owww_exclusivity || 0,
        onChange: v => setMeta('owww_exclusivity', parseInt(v||0,10))
      } ),
      el( NumberControl, {
        label: 'Dauer (Minuten)', min:0, step:10,
        value: meta.owww_time_relaxed || 0,
        onChange: v => setMeta('owww_time_relaxed', parseInt(v||0,10))
      } )
    );
  }

  registerPlugin( 'owww-panel', { render: Panel, icon: 'info' } );
} )( window.wp );
