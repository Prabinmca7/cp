YUI().use('node', function(Y) {
    // Code snippet toggler
    Y.all('#content a[data-toggle]').on('click', function(e) {
        e.halt();
        var link = e.target,
            switchTo = link.getAttribute('data-toggle');
        link.setAttribute('data-toggle', link.get('innerHTML')).set('innerHTML', switchTo)
            .next().toggleClass('hide');
    });
    // Expand+collapse any dt
    Y.all('#content dt').on('click', function(e) {
        e.currentTarget.toggleClass('collapsed').next().toggleClass('hide');
    });
    Y.all('#content > dl > dt').insert('<span class="toggle">\u25BC</span>');
});