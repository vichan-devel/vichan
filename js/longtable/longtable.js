$.fn.longtable = function(fields, options, data) {
  const elem = $(this).classList.add('longtable');

  const orig_data = data;

  options.row_h    = options.row_h    || 22;
  options.checkbox = options.checkbox || false;

  const shown_rows = {};

  const sorted_by = undefined;
  const sorted_reverse = false;

  const filter = () => { return true; };

  const lt = {
    _gen_field_td: function(field, id) {
      const el;
      if (id === undefined) {
        el = document.querySelector('<th></th>');
        el.html(fields[field].name || field);

        if (!fields[field].sort_disable) {
          el.classList.add('sortable').click(() => {
            lt._sort_by(field);
          });
        }
      }
      else {
        el = document.querySelector('<td></td>');
        if (fields[field].fmt) { // Special formatting?
          el.html(fields[field].fmt(data[id]));
        }
        else {
          el.html(data[id][field]);
        }
      }
      el.css("width", fields[field].width);
      el.css("height", options.row_h);
      return el;
    },
    _gen_tr: (id) => {
      const el = document.querySelector('<tr></tr>');
      $.each(fields, function(field, f) {
        lt._gen_field_td(field, id).appendTo(el);
      });
      if (id !== undefined) {
        el.classList.add('row').addClass("row_"+id);
        el.css({position: "absolute", top: options.row_h * (id+1)});
      }
      return el;
    },
    _clean: () => {
      elem.querySelector('.row').remove();
      shown_rows = {};
    },
    _remove: (id) => {
      elem.find('.row_'+id).remove();
      delete shown_rows[id];
    },
    _insert: (id) => {
      const el = lt._gen_tr(id).appendTo(elem);
      $(elem).trigger("new-row", [data[id], el]);
      shown_rows[id] = true;
    },

    _sort_by: (field) => {
      if (field !== undefined) {
        if (sorted_by === field) {
          sorted_reverse = !sorted_reverse;
        }
        else {
          sorted_reverse = !!fields[field].sort_reverse;
          sorted_by = field;
        }
      }
      lt.sort_by(sorted_by, sorted_reverse);
    },

    _apply_filter: () => {
      data = data.filter(filter);      
    },
    _reset_data: () => {
      data = orig_data;
    },


    set_filter: (f) => {
      filter = f;
      lt._reset_data();
      lt._apply_filter();
      lt._sort_by();
    },

    sort_by: function(field, reverse) {
      if (field !== undefined) {
        sorted_by = field;
        sorted_reverse = reverse;       

        const ord = fields[field].sort_fun || function(a,b) { return lt.sort_alphanum(a[field], b[field]); };

        data = data.sort(ord);
        if (reverse) data = data.reverse();
      }

      lt.update_data();
    },

    update_viewport: () => {
      const first = $(window).scrollTop() - $(elem).offset().top - options.row_h;
      const last = first + $(window).height();

      first = Math.floor(first / options.row_h);
      last  = Math.ceil (last /  options.row_h);

      first = first < 0 ? 0 : first;
      last = last >= data.length ? data.length - 1 : last;

      $.each(shown_rows, (id) => {
        if (id < first || id > last) {
          lt._remove(id);
        }
      });

      for (var id = first; id <= last; id++) {
        if (!shown_rows[id]) lt._insert(id);
      }
    },

    update_data: () => {
      $(elem).height((data.length + 1) * options.row_h);

      lt._clean();
      lt.update_viewport();
    },

    get_data: () => {
      return data;
    },

    destroy: () => {
    },

    // http://web.archive.org/web/20130826203933/http://my.opera.com/GreyWyvern/blog/show.dml/1671288
    sort_alphanum: function(a, b) {
      function chunkify(t) {
        const tz = [], x = 0, y = -1, n = 0, i, j;

        while (i = (j = t.charAt(x++)).charCodeAt(0)) {
          const m = (/* dot: i === 46 || */(i >=48 && i <= 57));
          if (m !== n) {
            tz[++y] = "";
            n = m;
          }
          tz[y] += j;
        }
        return tz;
      }

      const aa = chunkify((""+a).toLowerCase());
      const bb = chunkify((""+b).toLowerCase());

      for (x = 0; aa[x] && bb[x]; x++) {
        if (aa[x] !== bb[x]) {
          const c = Number(aa[x]), d = Number(bb[x]);
          if (c === aa[x] && d === bb[x]) {
            return c - d;
          } else return (aa[x] > bb[x]) ? 1 : -1;
        }
      }
      return aa.length - bb.length;
    }
    // End of foreign code
  };



  lt._gen_tr().appendTo(elem);
  lt.update_data();

  $(window).on("scroll resize", lt.update_viewport);

  return lt;
};
