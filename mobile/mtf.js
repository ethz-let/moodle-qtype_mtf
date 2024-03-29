var that = this;
var result = {

    componentInit: function() {

        // This.question should be provided to us here.
        // This.question.html (string) is the main source of data, presumably prepared by the renderer.
        // There are also other useful objects with question like infoHtml which is used by the
        // page to display the question state, but with which we need do nothing.
        // This code just prepares bits of this.question.html storing it in the question object ready for
        // passing to the template (oumr.html).
        // Note this is written in 'standard' javascript rather than ES6. Both work.

        if (!this.question) {
            return that.CoreQuestionHelperProvider.showComponentError(that.onAbort);
        }

        // Create a temporary div to ease extraction of parts of the provided html.
        var div = document.createElement('div');
        div.innerHTML = this.question.html;

        // Replace Moodle's correct/incorrect classes, feedback and icons with mobile versions.
        that.CoreQuestionHelperProvider.replaceCorrectnessClasses(div);
        that.CoreQuestionHelperProvider.replaceFeedbackClasses(div);
        that.CoreQuestionHelperProvider.treatCorrectnessIcons(div);

        // Add the useful parts back into the question object ready for rendering in the template.
        var questiontext = div.querySelector('.qtext');
        this.question.text = questiontext.innerHTML;

        // Without the question text there is no point in proceeding.
        if (typeof this.question.text === 'undefined') {
            return that.CoreQuestionHelperProvider.showComponentError(that.onAbort);
        }

        // Determine whether the question has a deduction
        var hasdeduction = div.querySelector('.mtfresponsebutton.reset');
        this.question.hasdeduction = hasdeduction !== null;

        var prompt = div.querySelector('.prompt');
        this.question.prompt = prompt !== null ? prompt.innerHTML : null;

        // If the question has a deduction, the renderer will add a column (with a trash icon)
        // before the options, so the columns are c1 and c2 instead of c0 and c1
        var additionalcolumn = (this.question.hasdeduction ? 1 : 0);
        var headerc1 = div.querySelector('.que.mtf .generaltable thead .header.c' + (0 + additionalcolumn));
        this.question.headerc1 = headerc1 !== null ? headerc1.innerHTML : null;

        var headerc2 = div.querySelector('.que.mtf .generaltable thead .header.c' + (1 + additionalcolumn));
        this.question.headerc2 = headerc2 !== null ? headerc2.innerHTML : null;

        var scoringmethod = div.querySelector('.que.mtf [id^="scoringmethodinfo_q"]');
        this.question.scoringmethod = scoringmethod !== null ? scoringmethod.getAttribute('label') : null;

        var scoringmethodhelp = div.querySelector('.que.mtf [id^="scoringmethodinfo_q"] a');
        this.question.scoringmethodhelp = scoringmethodhelp !== null ? scoringmethodhelp.getAttribute('data-content') : null;

        var answeroptions = div.querySelector('.que.mtf .generaltable tbody');
        var options = [];
        var divs = answeroptions.querySelectorAll('tr');

        divs.forEach(function(d, i) {

            var text = d.querySelector('span.optiontext');
            text = (text !== null) ? text.innerHTML : null;

            var name = d.querySelector('.mtfresponsebutton.c' + (0 + additionalcolumn) + ' input');
            name = (name !== null) ? name.getAttribute('name') : null;

            var disabled = d.querySelector('input');
            disabled = (disabled !== null) ? (disabled.hasAttribute('disabled') ? true : false) : false;

            var feedback = d.querySelector('.mtfspecificfeedback')
            feedback = feedback !== null ? feedback.innerHTML : '';

            var qclass = d.getAttribute('class');

            var selection = null;
            if (d.querySelector('.mtfresponsebutton.c' + (0 + additionalcolumn) + ' input[type=radio]').checked) {
                selection = 1;
            } else if (d.querySelector('.mtfresponsebutton.c' + (1 + additionalcolumn) + ' input[type=radio]').checked) {
                selection = 2;
            }

            options.push({text: text, name: name, selection: selection, feedback: feedback, disabled: disabled, qclass: qclass});
        });

        this.question.options = options;

        return true;
    }
};

// This next line is required as is (because of an eval step that puts this result object into the global scope).
result;