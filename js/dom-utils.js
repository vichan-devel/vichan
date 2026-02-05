/**
 * DOM Utility Library - A vanilla JS replacement for jQuery
 * Provides methods for DOM manipulation, event handling, and AJAX
 */

// Selector utilities
const $ = {
  // Select single element or all elements
  select: function(selector, context = document) {
    return typeof selector === 'string' ? 
      (selector.startsWith('#') && !selector.includes(' ') ? 
        document.getElementById(selector.slice(1)) : 
        context.querySelector(selector)) : 
      selector;
  },

  selectAll: function(selector, context = document) {
    return Array.from(typeof selector === 'string' ? 
      context.querySelectorAll(selector) : 
      [selector]);
  },

  // Create element
  create: function(html) {
    const template = document.createElement('template');
    template.innerHTML = html.trim();
    return template.content.firstChild;
  },

  // Query selector shorthand
  on: function(selector, event, handler, context = document) {
    context.addEventListener(event, function(e) {
      const target = e.target.closest(selector);
      if (target) handler.call(target, e);
    });
  }
};

// Element prototype extensions
Element.prototype.$ = function(selector) {
  return this.querySelector(selector);
};

Element.prototype.$$ = function(selector) {
  return Array.from(this.querySelectorAll(selector));
};

Element.prototype.on = function(event, handler, useCapture = false) {
  this.addEventListener(event, handler, useCapture);
  return this;
};

Element.prototype.off = function(event, handler) {
  this.removeEventListener(event, handler);
  return this;
};

Element.prototype.addClass = function(...classes) {
  this.classList.add(...classes);
  return this;
};

Element.prototype.removeClass = function(...classes) {
  this.classList.remove(...classes);
  return this;
};

Element.prototype.toggleClass = function(className) {
  this.classList.toggle(className);
  return this;
};

Element.prototype.hasClass = function(className) {
  return this.classList.contains(className);
};

Element.prototype.setAttr = function(name, value) {
  if (value === null) this.removeAttribute(name);
  else this.setAttribute(name, value);
  return this;
};

Element.prototype.getAttr = function(name) {
  return this.getAttribute(name);
};

Element.prototype.removeAttr = function(name) {
  this.removeAttribute(name);
  return this;
};

Element.prototype.setCSS = function(prop, value) {
  if (typeof prop === 'object') {
    Object.assign(this.style, prop);
  } else {
    this.style[prop] = value;
  }
  return this;
};

Element.prototype.getCSS = function(prop) {
  return window.getComputedStyle(this).getPropertyValue(prop);
};

Element.prototype.setText = function(text) {
  this.textContent = text;
  return this;
};

Element.prototype.getText = function() {
  return this.textContent;
};

Element.prototype.setHTML = function(html) {
  this.innerHTML = html;
  return this;
};

Element.prototype.getHTML = function() {
  return this.innerHTML;
};

Element.prototype.setValue = function(value) {
  this.value = value;
  return this;
};

Element.prototype.getValue = function() {
  return this.value;
};

Element.prototype.append = function(...elements) {
  elements.forEach(el => {
    if (typeof el === 'string') {
      this.insertAdjacentHTML('beforeend', el);
    } else {
      this.appendChild(el);
    }
  });
  return this;
};

Element.prototype.prepend = function(...elements) {
  elements.reverse().forEach(el => {
    if (typeof el === 'string') {
      this.insertAdjacentHTML('afterbegin', el);
    } else {
      this.insertBefore(el, this.firstChild);
    }
  });
  return this;
};

Element.prototype.insertBefore = function(element) {
  this.parentNode.insertBefore(element, this);
  return this;
};

Element.prototype.insertAfter = function(element) {
  this.parentNode.insertBefore(element, this.nextSibling);
  return this;
};

Element.prototype.before = function(...elements) {
  elements.forEach(el => {
    if (typeof el === 'string') {
      this.insertAdjacentHTML('beforebegin', el);
    } else {
      this.parentNode.insertBefore(el, this);
    }
  });
  return this;
};

Element.prototype.after = function(...elements) {
  elements.forEach(el => {
    if (typeof el === 'string') {
      this.insertAdjacentHTML('afterend', el);
    } else {
      this.parentNode.insertBefore(el, this.nextSibling);
    }
  });
  return this;
};

Element.prototype.remove = function() {
  this.parentNode?.removeChild(this);
  return this;
};

Element.prototype.empty = function() {
  this.innerHTML = '';
  return this;
};

Element.prototype.find = function(selector) {
  return this.querySelector(selector);
};

Element.prototype.findAll = function(selector) {
  return Array.from(this.querySelectorAll(selector));
};

Element.prototype.closest = function(selector) {
  return Element.prototype.closest.call(this, selector);
};

Element.prototype.parent = function() {
  return this.parentElement;
};

Element.prototype.parents = function(selector) {
  const parents = [];
  let el = this.parentElement;
  while (el) {
    if (!selector || el.matches(selector)) {
      parents.push(el);
    }
    el = el.parentElement;
  }
  return parents;
};

Element.prototype.siblings = function() {
  return Array.from(this.parentElement.children).filter(el => el !== this);
};

Element.prototype.next = function() {
  return this.nextElementSibling;
};

Element.prototype.prev = function() {
  return this.previousElementSibling;
};

Element.prototype.clone = function(deep = true) {
  return this.cloneNode(deep);
};

Element.prototype.replaceWith = function(newElement) {
  this.parentNode.replaceChild(newElement, this);
  return this;
};

Element.prototype.setData = function(key, value) {
  this.dataset[key] = value;
  return this;
};

Element.prototype.getData = function(key) {
  return this.dataset[key];
};

Element.prototype.removeData = function(key) {
  delete this.dataset[key];
  return this;
};

// NodeList utilities
NodeList.prototype.forEach = function(fn) {
  Array.from(this).forEach(fn);
};

NodeList.prototype.on = function(event, handler) {
  this.forEach(el => el.addEventListener(event, handler));
};

// Array of elements utilities
Array.prototype.on = function(event, handler) {
  this.forEach(el => el.addEventListener(event, handler));
};

Array.prototype.addClass = function(...classes) {
  this.forEach(el => el.classList.add(...classes));
  return this;
};

Array.prototype.removeClass = function(...classes) {
  this.forEach(el => el.classList.remove(...classes));
  return this;
};

Array.prototype.setText = function(text) {
  this.forEach(el => el.textContent = text);
  return this;
};

Array.prototype.setHTML = function(html) {
  this.forEach(el => el.innerHTML = html);
  return this;
};

// Trigger custom events
Element.prototype.trigger = function(eventName, data = {}) {
  const event = new CustomEvent(eventName, { detail: data, bubbles: true, cancelable: true });
  this.dispatchEvent(event);
  return this;
};

// Fetch API wrapper
const ajax = {
  get: function(url, options = {}) {
    return fetch(url, {
      method: 'GET',
      ...options
    }).then(response => response.text()).then(data => {
      if (options.dataType === 'json') {
        return JSON.parse(data);
      }
      return data;
    });
  },

  post: function(url, data, options = {}) {
    let body = data;
    let headers = options.headers || {};

    if (data instanceof FormData) {
      body = data;
    } else if (typeof data === 'object') {
      body = new URLSearchParams(data);
      if (!headers['Content-Type']) {
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
      }
    }

    return fetch(url, {
      method: 'POST',
      headers,
      body,
      ...options
    }).then(response => {
      const contentType = response.headers.get('content-type');
      if (contentType?.includes('application/json')) {
        return response.json();
      }
      return response.text();
    });
  },

  request: function(options) {
    const { url, method = 'GET', data, headers = {}, dataType = 'text' } = options;
    let body = null;

    if (method !== 'GET' && data) {
      if (data instanceof FormData) {
        body = data;
      } else if (typeof data === 'object') {
        body = new URLSearchParams(data);
        if (!headers['Content-Type']) {
          headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
      }
    }

    return fetch(url, {
      method,
      headers: Object.keys(headers).length > 0 ? headers : undefined,
      body,
      ...options
    }).then(response => {
      if (dataType === 'json') {
        return response.json();
      }
      return response.text();
    });
  }
};

// Ready function - wait for DOM to be ready
const ready = function(callback) {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback);
  } else {
    callback();
  }
};

// Export for use
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { $, ajax, ready };
}
