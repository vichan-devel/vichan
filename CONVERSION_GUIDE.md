# jQuery → Vanilla JS Conversion Guide

This guide shows the patterns used to convert vichan's JavaScript from jQuery to modern vanilla JavaScript ES6.

## Quick Reference: Pattern Conversions

### DOM Selection

```javascript
// jQuery
$('#my-id')                    
$('.my-class')                 
$('div.class')                 
$(selector).find('child')       
$(selector).parent()           
$(selector).closest('parent')  
$(selector).next()             

// Vanilla JS / ES6
document.getElementById('my-id')
document.querySelectorAll('.my-class')
document.querySelectorAll('div.class')
element.querySelector('child')
element.parentElement
element.closest('parent')
element.nextElementSibling
```

### Event Handling

```javascript
// jQuery
$el.click(handler)
$el.on('click', handler)
$el.off('click', handler)
$(document).on('click', '.selector', handler)
$(document).trigger('event', data)

// Vanilla JS / ES6
el.addEventListener('click', handler)
el.addEventListener('click', handler)
el.removeEventListener('click', handler)
document.addEventListener('click', (e) => {
  if (e.target.matches('.selector')) handler(e);
})
document.dispatchEvent(new CustomEvent('event', {detail: data}))
```

### Class Manipulation

```javascript
// jQuery
$el.addClass('class')
$el.removeClass('class')
$el.hasClass('class')
$el.toggleClass('class')

// Vanilla JS / ES6
el.classList.add('class')
el.classList.remove('class')
el.classList.contains('class')
el.classList.toggle('class')
```

### Attributes & Properties

```javascript
// jQuery
$el.attr('name')
$el.attr('name', value)
$el.removeAttr('name')
$el.prop('checked', true)
$el.val()
$el.text()
$el.html()

// Vanilla JS / ES6
el.getAttribute('name')
el.setAttribute('name', value)
el.removeAttribute('name')
el.checked = true
el.value
el.textContent
el.innerHTML
```

### DOM Manipulation

```javascript
// jQuery
$el.append(child)
$el.prepend(child)
$el.insertAfter($el)
$el.insertBefore($el)
$el.replaceWith(new)
$el.remove()
$el.empty()
$el.clone()

// Vanilla JS / ES6
el.appendChild(child)
el.insertBefore(child, el.firstChild)
parent.insertBefore(el, el.nextSibling)
parent.insertBefore(el, ref)
parent.replaceChild(new, el)
el.remove()
el.innerHTML = ''
el.cloneNode(true)
```

### CSS & Styling

```javascript
// jQuery
$el.css('property')
$el.css('property', 'value')
$el.css({prop1: 'val1', prop2: 'val2'})
$el.show()
$el.hide()
$el.fadeIn()

// Vanilla JS / ES6
window.getComputedStyle(el).propertyName
el.style.property = 'value'
Object.assign(el.style, {property1: 'value1'})
el.style.display = ''
el.style.display = 'none'
el.style.opacity = '1'; // then transition
```

### AJAX Requests

```javascript
// jQuery
$.ajax({
  url: 'endpoint',
  type: 'POST',
  data: formData,
  success: fn,
  error: fn
})

$.get(url, callback)
$.post(url, data, callback)

// Vanilla JS / ES6
fetch('endpoint', {
  method: 'POST',
  body: formData
}).then(r => r.json()).then(fn).catch(err)

fetch(url).then(r => r.text()).then(fn)
fetch(url, {method: 'POST', body: data})
  .then(r => r.json()).then(fn)
```

### Iteration

```javascript
// jQuery
$('selector').each(function(i, el) {
  $(this).doSomething()
})

$.each(array, function(i, val) {
  console.log(val)
})

// Vanilla JS / ES6
document.querySelectorAll('selector').forEach((el, i) => {
  el.doSomething()
})

array.forEach((val, i) => {
  console.log(val)
})
```

### Ready State

```javascript
// jQuery
$(document).ready(function() {
  // code
})

$(window).ready(function() {
  // code  
})

// Vanilla JS / ES6 (vichan uses onReady)
onReady(() => {
  // code
})

// Or vanilla:
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    // code
  })
} else {
  // code (DOM already loaded)
}
```

### Data Attributes

```javascript
// jQuery
$el.data('key', value)
$el.data('key')
$el.removeData('key')

// Vanilla JS / ES6
el.dataset.key = value
el.dataset.key
delete el.dataset.key
```

### jQuery Methods Used in vichan

#### Commonly Found:
- `.addClass()` → `.classList.add()`
- `.removeClass()` → `.classList.remove()`
- `.toggleClass()` → `.classList.toggle()`
- `.hasClass()` → `.classList.contains()`
- `.on()` → `.addEventListener()`
- `.off()` → `.removeEventListener()`
- `.attr()` → `.getAttribute()` / `.setAttribute()`
- `.removeAttr()` → `.removeAttribute()`
- `.text()` → `.textContent`
- `.html()` → `.innerHTML`
- `.val()` → `.value`
- `.find()` → `.querySelector()` / `.querySelectorAll()`
- `.parent()` → `.parentElement`
- `.closest()` → `.closest()` (already native)
- `.next()` → `.nextElementSibling`
- `.prev()` → `.previousElementSibling`
- `.append()` → `.appendChild()`
- `.prepend()` → `.insertBefore()`
- `.remove()` → `.remove()`
- `.show()` / `.hide()` → `.style.display`
- `.click()` → `.addEventListener('click')`
- `.submit()` → `.addEventListener('submit')`
- `.change()` → `.addEventListener('change')`
- `.each()` → `.forEach()`
- `.length` → `.length` (NodeList) or check `if (el)`

## Examples from Converted Files

### Example 1: Event Handling (from id_highlighter.js)

**Before (jQuery)**:
```javascript
$(".poster_id").on('click', id_highlighter);
$(document).on('new_post', function(e, post) {
  $(post).find('.poster_id').on('click', id_highlighter);
});
```

**After (Vanilla JS)**:
```javascript
document.querySelectorAll(".poster_id").forEach(el => {
  el.addEventListener('click', id_highlighter);
});

document.addEventListener('new_post', (e) => {
  const post = e.detail;
  const posterIds = post.querySelectorAll('.poster_id');
  posterIds.forEach(el => {
    el.addEventListener('click', id_highlighter);
  });
});
```

### Example 2: DOM Manipulation (from catalog-search.js)

**Before (jQuery)**:
```javascript
$('.replies').each(function () {
  let subject = $(this).children('.intro').text().toLowerCase();
  $(this).parents('div[id="Grid"]>.mix').css('display', 'none');
});
```

**After (Vanilla JS)**:
```javascript
const replies = document.querySelectorAll('.replies');
replies.forEach(replyEl => {
  const subject = replyEl.querySelector('.intro')
    ?.textContent.toLowerCase() || '';
  const mixElement = replyEl.closest('.mix');
  if (mixElement) {
    mixElement.style.display = 'none';
  }
});
```

### Example 3: AJAX (from ajax.js)

**Before (jQuery)**:
```javascript
$.ajax({
  url: this.action,
  type: 'POST',
  data: formData,
  success: function(response) { /* ... */ },
  error: function(xhr) { /* ... */ }
});
```

**After (Vanilla JS with Fetch)**:
```javascript
try {
  const response = await postWithProgress(form.action, formData, form);
  if (response.error) {
    // handle error
  }
} catch (error) {
  console.error('AJAX Error:', error);
}

// Helper function
const postWithProgress = (url, formData, form) => {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.upload.addEventListener('progress', (e) => {
      // update progress
    });
    xhr.addEventListener('load', () => {
      resolve(JSON.parse(xhr.responseText));
    });
    xhr.open('POST', url);
    xhr.send(formData);
  });
};
```

## Tips for Converting

1. **Keep functionality identical** - Don't refactor logic while converting syntax
2. **Test thoroughly** - Each converted file should work exactly like before  
3. **Use const/let** - Replace `var` with `const` (prefer const, use let only if reassigned)
4. **Arrow functions** - Use for callbacks, but consider `this` context
5. **Template literals** - Replace string concatenation with backticks
6. **Use === and !==** - Avoid == and != for loose comparison
7. **Preserve comments** - Keep all JSDoc and inline comments
8. **Handle null/undefined** - Use optional chaining (`?.`) and nullish coalescing (`??`)
9. **Test on old browsers** - Some conversions may need polyfills for older browsers
10. **Consider using dom-utils.js** - For common operations, the utility library is available

## Converting a File: Step-by-Step

1. **Identify all jQuery patterns** in the file
2. **Convert ready functions**: `$(document).ready()` → `onReady()`
3. **Convert selectors**: `$()` → `querySelector()`/`querySelectorAll()`
4. **Convert event handlers**: `.on()` → `.addEventListener()`
5. **Convert class operations**: `.addClass()` → `.classList.add()`
6. **Convert DOM manipulation**: `.append()` → `.appendChild()`
7. **Convert AJAX**: `$.ajax()` → `fetch()`
8. **Convert var to const/let**
9. **Use arrow functions** for callbacks
10. **Validate**: Run `node -c filename.js` to check syntax
11. **Test functionality** in browser

## Resources

- [MDN: Document API](https://developer.mozilla.org/en-US/docs/Web/API/Document)
- [MDN: Element API](https://developer.mozilla.org/en-US/docs/Web/API/Element)
- [MDN: Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API)
- [MDN: EventTarget.addEventListener](https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener)
- [MDN: Using CustomEvent](https://developer.mozilla.org/en-US/docs/Web/Events/Creating_and_triggering_events#Creating_custom_events)

## Available Utility Library

A DOM utility library is available at `js/dom-utils.js` that provides jQuery-like syntax for vanilla JS:

```javascript
// Examples from dom-utils.js
element.addClass('class')           // .classList.add()
element.removeClass('class')        // .classList.remove()
element.find('.child')              // .querySelector()
element.on('click', handler)        // .addEventListener()
element.setAttr('name', 'value')    // .setAttribute()
element.setText('text')             // .textContent
```

Refer to dom-utils.js for the complete list of available methods.
