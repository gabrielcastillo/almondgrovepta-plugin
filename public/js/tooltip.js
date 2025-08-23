/*
 * File: tooltip.js
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */

(function() {
    let tooltip;

    function createTooltip(content, options = {}) {
        tooltip = document.createElement('div');
        tooltip.className = 'custom-tooltip';
        tooltip.innerHTML = content;

        // Apply custom styles
        if (options.styles && typeof options.styles === 'object') {
            for (let key in options.styles) {
                tooltip.style[key] = options.styles[key];
            }
        }

        document.body.appendChild(tooltip);
    }

    function positionTooltip(el, position = 'top') {
        if (!tooltip || !el) return;

        const rect = el.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

        const tooltipRect = tooltip.getBoundingClientRect();
        const spacing = 8;

        let top, left;

        switch (position) {
            case 'bottom':
                top = rect.bottom + spacing + scrollTop;
                left = rect.left + (rect.width - tooltipRect.width) / 2 + scrollLeft;
                break;
            case 'left':
                top = rect.top + (rect.height - tooltipRect.height) / 2 + scrollTop;
                left = rect.left - tooltipRect.width - spacing + scrollLeft;
                break;
            case 'right':
                top = rect.top + (rect.height - tooltipRect.height) / 2 + scrollTop;
                left = rect.right + spacing + scrollLeft;
                break;
            case 'top':
            default:
                top = rect.top - tooltipRect.height - spacing + scrollTop;
                left = rect.left + (rect.width - tooltipRect.width) / 2 + scrollLeft;
        }

        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
        tooltip.style.opacity = 1;
    }

    function hideTooltip() {
        if (tooltip) {
            tooltip.style.opacity = 0;
            tooltip.remove();
            tooltip = null;
        }
    }

    function attachTooltip(el, content, options = {}) {
        const position = options.position || 'top';

        el.addEventListener('mouseenter', () => {
            createTooltip(content, options);
            positionTooltip(el, position);
        });

        el.addEventListener('mousemove', () => {
            positionTooltip(el, position);
        });

        el.addEventListener('mouseleave', () => {
            hideTooltip();
        });
    }

    // Expose to global
    window.CustomTooltip = {
        attach: attachTooltip
    };
})();
