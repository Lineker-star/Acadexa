// SVG icons for markup built in JavaScript; same sprite as the Blade <x-icon> component.
// The versioned sprite URL is provided by the layout (partials/pwa-head) as window.ACADEXXA_ICONS.

export function icon(name, className = '', style = '') {
    const id = String(name || 'circle').split(/\s+/).find(t => t.startsWith('bi-'))?.slice(3) || String(name).replace(/^bi-/, '');
    const sprite = window.ACADEXXA_ICONS || '/icons.svg';
    const styleAttr = style ? ` style="${style}"` : '';
    return `<svg class="icon${className ? ' ' + className : ''}"${styleAttr} width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false"><use href="${sprite}#${id}"/></svg>`;
}

window.acadexxaIcon = icon;
