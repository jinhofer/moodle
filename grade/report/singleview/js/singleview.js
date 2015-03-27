M.gradereport_singleview = {};

M.gradereport_singleview.init = function(Y) {
    // Make toggle links
    Y.all('.include').each(function(link) {
        var type = link.getAttribute('class').split(" ")[2];

        var toggle = function(checked) {
            return function(input) {
                input.getDOMNode().checked = checked;
                Y.Event.simulate(input.getDOMNode(), 'change');
            };
        };

        link.on('click', function(e) {
            e.preventDefault();
            Y.all('input[name^=' + type + ']').each(toggle(link.hasClass('all')));
        });
    });

    if(Y.one('button#toggle-search')) {
        var advanced;
        Y.all('button#toggle-search').each(function() {
            // Detach previous listeners, if any
            this.detach();
            this.on('click', function() {
                advanced = this.hasClass('advanced');
                Y.all('div.selectitems div.singleselect select').each(function() {
                    if(advanced) {
                        this.setAttribute('size', 1);
                        Y.all('div.filter').each(function() {
                            this.addClass('hidden');
                        });
                    } else {
                        this.setAttribute('size', 15);
                        Y.all('div.filter').each(function() {
                            this.removeClass('hidden');
                        });
                    }
                });
                Y.all('button#toggle-search').each(function() {this.toggleClass('advanced');});
            });
        });
    }

    Y.all('div.selectitems div.singleselect').each(function() {
        if (this.one('div.filter')) {
            // We alread added a filter, so no need to add another
            return;
        }

        var id,
            html = '<div class="hidden filter"><label for="';

        if(this.one('input[name=item]').getAttribute('value') == 'grade') {
            id = 'grade-search';
            html += id+'">'+M.util.get_string('searchitemlist', 'gradereport_singleview');
        } else {
            id = 'user-search';
            html += id+'">'+M.util.get_string('searchuserlist', 'gradereport_singleview');
        }
        html += '</label><br><input id="'+id+'" type="text" autocomplete="off"></div>';
        this.append(html);
    });

    Y.all('div.filter input').each(function() {
        var value,
            optionValue,
            clone,
            innerHTML,
            text;

        this.on('valueChange', function() {
            // User made a change to the search field
            if(this.get('value') === '') {
                // User cleared the search content, reset all values
                this.ancestor('div.singleselect').all('select span').each(function() {
                    // I would LOVE to use class="hidden", but IE dislikes that
                    optionValue = this.getAttribute('value');
                    innerHTML = this.getHTML();
                    clone = Y.Node.create('<option></option>');
                    clone.setAttribute('value', optionValue);
                    clone.setHTML(innerHTML);
                    this.replace(clone);
                });
                return;
            }
            this.ancestor('div.singleselect').all('select option, select span').each(function() {
                value = this.getHTML().toLowerCase();
                optionValue = this.getAttribute('value');
                innerHTML = this.getHTML();
                text = this.ancestor('div.singleselect').one('div.filter input').get('value').toLowerCase();
                if(value.indexOf(text) === -1 && this.get('value') !== '' && !this.hasClass('hidden')) {
                    // The option does not contain this string, replace it with span for IE
                    clone = Y.Node.create('<span class="hidden"></span>');
                    clone.setAttribute('value', optionValue);
                    clone.setHTML(innerHTML);
                    this.replace(clone);
                } else {
                    // The option does contain the string, so make it vidible
                    if (value.indexOf(text) !== -1 && this.hasClass('hidden')) {
                        optionValue = this.getAttribute('value');
                        innerHTML = this.getHTML();
                        clone = Y.Node.create('<option></option>');
                        clone.setAttribute('value', optionValue);
                        clone.setHTML(innerHTML);
                        this.replace(clone);
                    }
                }
            });
        });
    });

    // Override Toggle
    Y.all('input[name^=override_]').each(function(input) {
        input.on('change', function() {
            var checked = input.getDOMNode().checked;
            var names = input.getAttribute('name').split("_");

            var itemid = names[1];
            var userid = names[2];

            var interest = '_' + itemid + '_' + userid;

            Y.all('input[name$=' + interest + ']').filter('input[type=text]').each(function(text) {
                text.getDOMNode().disabled = !checked;
            });
            // deal with scales that are not text... UCSB
            Y.all('select[name$=' + interest + ']').each(function(select) {
                select.getDOMNode().disabled = !checked;
            });
        });
    });
};
