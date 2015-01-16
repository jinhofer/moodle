/** Flow Viewer to visualize Lesson tool */

YUI.add('moodle-mod_lesson-flowviewer', function(Y) {
    // Define a name space to call
    M.mod_lesson = M.mod_lesson || {};
    M.mod_lesson.flowviewer = {
        init: function(data) {
            jsPlumb.ready(function() {
                var fv = new FlowViewer();
                fv.init(data, {
                    chart_container: Y.one('#chart-container'),
                    control_container: Y.one('#control-container')});
            });
        }
    };
}, '@VERSION@', {
    requires: ['node', 'dd', 'dd-constrain', 'anim', 'node-event-simulate']
});



var FlowViewer = function() {
    var self = this;

    this.lesson            = null;
    this.pages             = null;
    this.chart_container   = null;
    this.control_container = null;
    this.current_zoom      = 0;
    this.zoom_range        = {min: -4, max: 4};
    this.show_label        = true;
    this.canvas_size       = {width: 800, height: 800};

    // initialize a jsPlumn instance
    this.plumb = jsPlumb.getInstance({
        // default drag options
        DragOptions : { cursor: 'pointer', zIndex:2000 },
        Connector: [ 'StateMachine', {margin: 1}],
//        Connector: [ 'Bezier', {curviness: 100}],
//        Connector: [ 'Straight', {stub: 20}],
        // the overlays to decorate each connection with.  note that the label overlay uses a function to generate the label text; in this
        // case it returns the 'labelText' member that we set on each connection in the 'init' method below.
        ConnectionOverlays : [
            [ 'Arrow', {location: 1, width: 10, length: 15 } ]
        ],
        PaintStyle: {
            lineWidth: 1,
            strokeStyle: '#567567',
        },
        EndPoint: ['Dot', {radius: 3}],
        Container: self.chart_container
    });


    /**
     * initialize the module
     */
    this.init = function(data, options) {
        self.lesson    = data.lesson;       // lesson info
        self.pages     = data.pages;        // list of pages
        self.control_container  = options.control_container;
        self.chart_container    = options.chart_container;
        self.max_conn_degree = 0;           // max number of connections counted on any page

        self.build_controller(data);

        // prep extra info for the data
        for (var page_id in self.pages) {
            self.pages[page_id].conn_src_count  = 0;
            self.pages[page_id].conn_dest_count = 0;
        }

        for (var page_id in self.pages) {
            // count the number of connection (as source and target)
            for (var answer_id in self.pages[page_id].answers) {
                self.pages[page_id].conn_src_count++;

                var jumpto = self.pages[page_id].answers[answer_id].jumpto;
                if (typeof self.pages[jumpto] == 'undefined') {
                    self.pages[jumpto] = {
                        id: jumpto,
                        title: '',
                        qtype: null,
                        answers: [],
                        conn_src_count: 0,
                        conn_dest_count: 0
                    };
                }

                self.pages[jumpto].conn_dest_count++;
            }
        }

        self.prep_special_page();

        // add the questions to the DOM
        for (var i in self.pages) {
            var page = self.pages[i];
            var page_el = Y.Node.create('<div class="lesson-flowviewer-page" id="fv_page_' + page.id +
                                        '">' + self.truncate_text(page.title, 30) + '</div>');

            if (page.id == self.lesson.firstpageid) {
                page_el.addClass('firstpage');
            }

            self.chart_container.append(page_el);

            // adjust the size depending on the number of connections
            var conn_count = page.conn_src_count + page.conn_dest_count;
            if (conn_count > self.max_conn_degree) {
                self.max_conn_degree = conn_count;
            }

            if (conn_count > 4) {
                page_el.setStyles({
                    width: parseInt(page_el.getComputedStyle('width'),10) + (conn_count-4)*3 + 'px',
                    height: parseInt(page_el.getComputedStyle('height'),10) + (conn_count-4)*2 + 'px'
                });
            }

            self.plumb.makeSource(page_el, {
                anchor: 'Continuous',
                endpoint: ['Dot', { radius: 3 }],
                maxConnections: 50
            });

            self.plumb.makeTarget(page_el, {
                anchor: 'Continuous',
                endpoint: ['Dot', { radius: 3 }],
                maxConnections: 50
            });
        }

        // calculate the layout
        var layout = this.build_layout_d3();

        // position the page-elements
        for (var node_id in layout) {
            Y.one('#fv_page_'+node_id).setStyles({
                top: layout[node_id].y,
                left: layout[node_id].x
            });
        }

        // connect the page-elements
        self.plumb.setSuspendDrawing(true);
        for (var page_id in self.pages) {
            var page_color = '#0c0'; //self.random_color();

            for (var answer_id in self.pages[page_id].answers) {
                var answer = self.pages[page_id].answers[answer_id];

                var label_spacing = self.pages[page_id].conn_src_count;

                if (self.pages[page_id].conn_src_count > 10) {
                    label_spacing = 10;
                }

                var link_color = page_color;
                if (self.pages[page_id].prevpageid == answer.jumpto) {
                    link_color = '#bbb';
                }
                else if (self.pages[page_id].nextpageid == answer.jumpto) {
                    link_color = '#00f';
                }

                try {
                    self.plumb.connect({
                        source: 'fv_page_' + page_id,
                        target: 'fv_page_' + answer.jumpto,
                        overlays: [['Label', {
                            label: self.truncate_text(answer.answer, 10, false) + ':' + answer.score,
                            location: 0.05 + Math.random()*label_spacing*0.05,
                            cssClass: 'aLabel'
                        }]],
                        paintStyle: {
                            strokeStyle: link_color
                        }
                    });
                }
                catch(e) {
                    console.log(e);
                }
            }
        }

        self.plumb.setSuspendDrawing(false, true);

        // disable drag-n-drop, for version 1
        self.plumb.unmakeEverySource();
        self.plumb.unmakeEveryTarget();
    };


    /**
     * build the control widget (zoom, links, ...)
     */
    this.build_controller = function(data) {
        var ctn = self.control_container;

        // add the zoom buttons
        var zoom_in  = Y.Node.create('<input type="button" class="zoom-in button active" value="+" />');
        var zoom_out = Y.Node.create('<input type="button" class="zoom-out button active" value="&ndash;" />');
        var toggle_label = Y.Node.create('<input type="button" class="toggle-label button active" value="Answers" />');
        var give_feedback = Y.Node.create('<a target="_blank" class="give-feedback button" href="' +
                                          data.feedback_link +'">Give feedback</div>');
        var help = Y.Node.create('<a target="_blank" class="help button" href="' +
                                 data.help_link + '">Help</div>');

        zoom_in.appendTo(ctn);
        zoom_out.appendTo(ctn);
        toggle_label.appendTo(ctn);
        give_feedback.appendTo(ctn);
        help.appendTo(ctn);

        zoom_in.on('click', function() { self.set_zoom(self.current_zoom + 1);});
        zoom_out.on('click', function() { self.set_zoom(self.current_zoom - 1);});
        toggle_label.on('click', function() { self.toggle_label(); });
    };

    /**
     * set the chart/graph to a zoom level, 0 = original size
     */
    this.set_zoom = function(zoom_level) {
        if (zoom_level < self.zoom_range.min || zoom_level > self.zoom_range.max) {
            return false;
        }

        self.current_zoom = zoom_level;
        var zoom_value = 1 + (zoom_level * 0.2);

        var p = [ '-webkit-', '-moz-', '-ms-', '-o-', '' ],
        s = 'scale(' + zoom_value + ')';

        for (var i = 0; i < p.length; i++)
            self.chart_container.setStyle(p[i] + 'transform', s);

        self.plumb.setZoom(zoom_value);

        // hide the labels if zoom is less than -1
        if (zoom_level < -1) {
            self.toggle_label(false);
        }
        else {
            self.toggle_label(true);
        }

        // enable/disable the buttons accordingly
        if (zoom_level <= self.zoom_range.min) {
            Y.one('.button.zoom-out').removeClass('active');
        }
        else {
            Y.one('.button.zoom-out').addClass('active');
        }

        if (zoom_level >= self.zoom_range.max) {
            Y.one('.button.zoom-in').removeClass('active');
        }
        else {
            Y.one('.button.zoom-in').addClass('active');
        }
    };


    /**
     * toggle the connection labels on/off
     * @param bool show, optional
     */
    this.toggle_label = function(show) {
        var new_state = !self.show_label;

        // toggle the label state if not specified
        if (typeof show != 'undefined') {
            new_state = true && show;
        }

        // set the label visibility
        var button = Y.one('.button.toggle-label');

        if (new_state) {
            self.show_label = true;
            button.addClass('active');
            Y.all('.aLabel').removeClass('no-label');
        }
        else {
            self.show_label = false;
            button.removeClass('active');
            Y.all('.aLabel').addClass('no-label');
        }
    };


    /*
     * calculate the layout using D3 force
     */
    this.build_layout_d3 = function() {
        var force = d3.layout.force();
        force.linkDistance(14);
        force.chargeDistance(40);
        force.gravity(0.2);

        force.charge(function(node, ind) {
            var page = self.pages[self.d3_nodes[ind]];
            var conn_count = page.conn_src_count + page.conn_dest_count;
            return -160 - (conn_count > 5 ? conn_count*5 : 0);
        });

        // add the nodes
        self.d3_nodes = [];
        var page_count = self.lesson.page_count;

        var i = 0;
        for (var page_id in self.pages) {
            self.d3_nodes.push(page_id);

            var added = false;

            // set the first page near the top-left corner if it doesn't seem to be a hub
            if (page_id == self.lesson.firstpageid) {
                var conn_count = self.pages[page_id].conn_src_count + self.pages[page_id].conn_dest_count;
                if (conn_count < 5) {
                    added = true;
                    force.nodes().push({x: -(self.lesson.page_count*2+2), y: -(self.lesson.page_count*2+2), fixed: true});
                }
            }

            // align the other pages into a square, to help distribute them evenly
            if (added == false) {
                if (page_count < 4) {
                    var rand_x = i%2 == 0 ? -1 : 1;
                    var rand_y = i%2 == 0 ? 1 : -1;
                    force.nodes().push({x: i*2+rand_x, y: i*2+rand_y});
                }
                else {
                    if (i < page_count/4) {
                        var x = i, y = 0;
                    }
                    else if (i < page_count/2){
                        var x = page_count/4, y = (i - page_count/4);
                    }
                    else if (i < page_count*3/4){
                        var x = page_count*3/4 - i, y = page_count/4;
                    }
                    else {
                        var x = 0, y = page_count - i;
                    }

                    force.nodes().push({y: x*8, x: y*8});
                }
            }

            self.pages[page_id].d3_index = i;
            i++;
        }

        // reduce the distance if we have small number of nodes
        if (i < 30 && self.max_conn_degree < 9) {
            force.linkDistance(11);
            force.chargeDistance(30);
        }

        // add the links
        for (var page_id in self.pages) {
            var page = self.pages[page_id];

            for (var answer_id in page.answers) {
                var answer = page.answers[answer_id];
                force.links().push({source: page.d3_index, target: self.pages[answer.jumpto].d3_index});
            }
        }


        force.start();
        for (var i = 0; i < 300; ++i) { force.tick(); }
        force.stop();

        var node_layout = {};
        var nodes = force.nodes();

        // find the top-left corner
        var top = 0, left = 0;
        for (var i = 0; i < nodes.length; i++) {
            left = nodes[i].x < left ? nodes[i].x : left;
            top  = nodes[i].y < top ? nodes[i].y : top;
        }

        for (var i = 0; i < nodes.length; i++) {
            var node = nodes[i];
            node_layout[self.d3_nodes[i]] = {
                    x: (node.x - left)*16,
                    y: (node.y - top)*11
            };
        }

        return node_layout;
    };


    /**
     * format the special pages (EOL, ...)
     */
    this.prep_special_page = function() {
        if (typeof self.pages['UNSEENPAGE'] != 'undefined') {
            self.pages['UNSEENPAGE'].title = 'UNSEEN PAGE';
        }

        if (typeof self.pages['UNANSWEREDPAGE'] != 'undefined') {
            self.pages['UNANSWEREDPAGE'].title = 'UNANSWERED PAGE';
        }

        if (typeof self.pages['EOL'] != 'undefined') {
            self.pages['EOL'].title = 'END OF LESSON';
        }

        if (typeof self.pages['UNSEENBRANCHPAGE'] != 'undefined') {
            self.pages['UNSEENBRANCHPAGE'].title = 'UNSEEN BRANCH PAGE';
        }

        if (typeof self.pages['RANDOMPAGE'] != 'undefined') {
            self.pages['RANDOMPAGE'].title = 'RANDOM PAGE';
        }

        if (typeof self.pages['RANDOMBRANCH'] != 'undefined') {
            self.pages['RANDOMBRANCH'].title = 'RANDOM BRANCH';
        }

        if (typeof self.pages['CLUSTERJUMP'] != 'undefined') {
            self.pages['CLUSTERJUMP'].title = 'CLUSTER JUMP';
        }
    };


    /**
     * helper function to cut text to a specific length
     * @param string str
     * @param int max_length
     * @param bool hellip, whether to append hellip to truncated string
     */
    this.truncate_text = function(str, max_length, hellip) {
        if (str == null || str.length <= max_length) {
            return str;
        }

        if (typeof hellip == 'undefined') {
            hellip = true;
        }

        str = str.substring(0, max_length);

        var re = /\W\D/ig;
        var final_length = str.length;

        while (true) {
            var match = re.exec(str);

            if (match == null) {
                str = str.substring(0, final_length);
                break;
            }
            else {
                final_length = match.index;
            }
        }

        return hellip ? str + '&hellip;' : str;
    };

    /**
     * helper function to return random not-too-light colors
     */
    this.random_color = function(h, s, l) {
        if (typeof h == 'undefined') {
            h = Math.random();
        }

        if (typeof s == 'undefined') {
            s = Math.random()*0.5+0.5;
        }

        if (typeof l == 'undefined') {
            l = Math.random()*0.5;
        }

        var rgb = hslToRgb(h, s, l);
        return 'rgb(' + Math.round(rgb[0]) + ',' +
                        Math.round(rgb[1]) + ',' +
                        Math.round(rgb[2]) + ')';
    };


    /**
     * Converts an HSL color value to RGB. Conversion formula
     * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
     * Assumes h, s, and l are contained in the set [0, 1] and
     * returns r, g, and b in the set [0, 255].
     *
     * @param   Number  h       The hue
     * @param   Number  s       The saturation
     * @param   Number  l       The lightness
     * @return  Array           The RGB representation
     */
    function hslToRgb(h, s, l){
        var r, g, b;

        if(s == 0){
            r = g = b = l; // achromatic
        }else{
            function hue2rgb(p, q, t){
                if(t < 0) t += 1;
                if(t > 1) t -= 1;
                if(t < 1/6) return p + (q - p) * 6 * t;
                if(t < 1/2) return q;
                if(t < 2/3) return p + (q - p) * (2/3 - t) * 6;
                return p;
            }

            var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            var p = 2 * l - q;
            r = hue2rgb(p, q, h + 1/3);
            g = hue2rgb(p, q, h);
            b = hue2rgb(p, q, h - 1/3);
        }

        return [r * 255, g * 255, b * 255];
    }
};
