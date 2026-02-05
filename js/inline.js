onReady(() => {
  const App = {
    cache: {},
    
    get(url, cb) {
      const cached = App.cache[url];
      if (cached) {
        return cb(cached);
      }

      fetch(url)
        .then(response => response.text())
        .then(data => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(data, 'text/html');
          App.cache[url] = doc;
          cb(doc);
        })
        .catch(err => console.error('Failed to fetch:', err));
    },

    options: {
      add(key, description, tab = 'general') {
        const checked = App.options.get(key);
        const el = document.createElement('div');
        el.innerHTML = `
          <label>
            <input type="checkbox" ${checked ? 'checked' : ''}>
            ${description}
          </label>
        `;

        const checkbox = el.querySelector('input');
        checkbox.addEventListener('change', App.options.check(key));

        if (window.Options && window.Options.extend_tab) {
          window.Options.extend_tab(tab, el);
        }
      },

      get(key) {
        const val = localStorage[key];
        return val ? JSON.parse(val) : null;
      },

      check(key) {
        return (e) => {
          localStorage[key] = JSON.stringify(e.target.checked);
        };
      }
    }
  };

  const inline = function(e) {
    e.preventDefault();

    const root = this.closest('.post');
    const targetNum = this.textContent.slice(2);
    
    const threadEl = root.closest('[id^=thread]');
    const srcOP = threadEl ? threadEl.id.match(/\d+/)[0] : null;

    let node, targetOP;
    const isBacklink = !!this.className;
    
    if (isBacklink) {
      node = root.querySelector('> .intro') || root.querySelector('.intro');
      targetOP = srcOP;
    } else {
      node = this;
      const to_search = typeof inMod !== 'undefined' && inMod ? this.search : this.pathname;
      const match = to_search.match(/(\d+)\.html/);
      targetOP = match ? match[1] : null;
    }

    const link = {
      id: 'inline_' + targetNum,
      isBacklink: isBacklink,
      node: node
    };

    const selector = targetNum === targetOP
      ? 'div#op_' + srcOP
      : 'div#reply_' + targetNum;

    const clone = root.querySelector('div#inline_' + targetNum);
    if (clone) {
      clone.remove();
      const original = document.querySelector(selector);
      if (original) {
        original.style.display = '';
        const next = original.nextElementSibling;
        if (next) next.style.display = '';
      }
      return;
    }

    if (srcOP === targetOP) {
      if (targetNum === targetOP) {
        link.node = link.node.nextElementSibling; // bypass `(OP)`
      }

      const target = document.querySelector(selector);
      if (target) {
        return add(link, target);
      }
    }

    const loading = document.createElement('div');
    loading.className = 'inline post';
    loading.id = link.id;
    loading.textContent = 'loading...';
    link.node.parentNode.insertBefore(loading, link.node.nextSibling);

    App.get(this.pathname, ($page) => {
      loading.remove();
      const target = $page.querySelector(selector);
      if (target) {
        add(link, target);
      }
    });
  };

  const add = (link, target) => {
    const clone = target.cloneNode(true);

    if (link.isBacklink && App.options.get('hidePost')) {
      target.style.display = 'none';
      const next = target.nextElementSibling;
      if (next) next.style.display = 'none';
    }

    clone.querySelectorAll('.inline').forEach(el => el.remove());
    clone.className = 'inline post';
    clone.id = link.id;
    clone.style.cssText = ''; // Remove post hover styling
    
    link.node.parentNode.insertBefore(clone, link.node.nextSibling);
  };

  App.options.add('useInlining', _('Enable inlining'));
  App.options.add('hidePost', _('Hide inlined backlinked posts'));

  const style = document.createElement('style');
  style.textContent = `
    .inline {
      border: 1px dashed black;
      white-space: normal;
      overflow: auto;
    }
  `;
  document.head.appendChild(style);

  // Don't attach to outbound links
  if (App.options.get('useInlining')) {
    const assign_inline = () => {
      // Select all links that point to current location
      const links = document.querySelectorAll('.body a[href*="' + location.pathname + '"]');
      links.forEach(link => {
        if (!link.hasAttribute('rel') && !link.closest('.toolong')) {
          link.removeAttribute('onclick');
          // Clone without listeners to remove old click handlers
          const newLink = link.cloneNode(true);
          link.parentNode.replaceChild(newLink, link);
          newLink.addEventListener('click', inline);
        }
      });
      
      // Also handle mentioned links
      document.querySelectorAll('.mentioned a').forEach(link => {
        link.addEventListener('click', inline);
      });
    };

    assign_inline();

    document.addEventListener('new_post', (e) => {
      assign_inline();
    });
  }
});


