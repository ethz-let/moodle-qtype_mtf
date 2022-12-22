@qtype @qtype_mtf @qtype_mtf_7
Feature: Step 7

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | c1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | c1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | c1        | Default for c1 |
    And the following "questions" exist:
      | questioncategory | qtype | name             | template     |
      | Default for c1   | mtf   | MTF-Question-001 | question_one |
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  @javascript
  Scenario: Test deduction and overriding of deduction by admin
    When I log in as "admin"
    And I navigate to "Plugins > Question types > Multiple True False (ETH)" in site administration
    And I should see "Default values for Multiple True/False questions."
    And I set the following fields to these values:
      | id_s_qtype_mtf_allowdeduction | 1 |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I choose "Edit question" action for "MTF-Question-001" in the question bank
    And I set the following fields to these values:
      | id_deduction | 0.5 |
    And I press "id_updatebutton"
    And I click on "Preview" "link"
    And I switch to "questionpreview" window
    And I click on "Preview options" "link"
    And I set the field "How questions behave" to "Immediate feedback"
    And I press "Start again with these options"
    And I click on ".qtype_mtf_row:contains('option text 1') input[value=1]" "css_element"
    And I click on ".qtype_mtf_row:contains('option text 2') input[value=1]" "css_element"
    And I press "Check"
    # The deduction should be made
    Then I should see "Mark 0.25 out of 1.00"
    And I switch to the main window

    And I navigate to "Plugins > Question types > Multiple True False (ETH)" in site administration
    And I should see "Default values for Multiple True/False questions."
    And I set the following fields to these values:
      | id_s_qtype_mtf_allowdeduction |  |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I choose "Edit question" action for "MTF-Question-001" in the question bank
    And I click on "Preview" "link"
    And I switch to "questionpreview" window
    And I click on "Preview options" "link"
    And I set the field "How questions behave" to "Immediate feedback"
    And I press "Start again with these options"
    And I click on ".qtype_mtf_row:contains('option text 1') input[value=1]" "css_element"
    And I click on ".qtype_mtf_row:contains('option text 2') input[value=1]" "css_element"
    And I press "Check"
    # Now, the deduction should not be made anymore, because the admin has not allowed it
    Then I should see "Mark 0.50 out of 1.00"

  @javascript
  Scenario: Testcase 10, 11 A
  # Change scoring Method to MTF1/0 and test evaluation.

    When I choose "Edit question" action for "MTF-Question-001" in the question bank
    And I click on "Scoring method" "link"
    And I click on "id_scoringmethod_mtfonezero" "radio"
    And I press "id_updatebutton"
    And I click on "Preview" "link"
    And I switch to "questionpreview" window
    And I click on "Preview options" "link"
    And I set the field "How questions behave" to "Immediate feedback"
    And I press "Start again with these options"
    And I click on ".qtype_mtf_row:contains('option text 1') input[value=1]" "css_element"
    And I click on ".qtype_mtf_row:contains('option text 2') input[value=2]" "css_element"
    And I press "Check"
    Then I should see "Mark 1.00 out of 1.00"
    And I press "Start again"
    And I click on ".qtype_mtf_row:contains('option text 1') input[value=1]" "css_element"
    And I click on ".qtype_mtf_row:contains('option text 2') input[value=1]" "css_element"
    And I press "Check"
    Then I should see "Mark 0.00 out of 1.00"

  @javascript
  Scenario: Testcase 10, 11 B
  # Change scoring Method to Subpoints and test evaluation.

    When I choose "Edit question" action for "MTF-Question-001" in the question bank
    And I click on "Scoring method" "link"
    And I click on "id_scoringmethod_subpoints" "radio"
    And I press "id_updatebutton"
    And I click on "Preview" "link"
    And I switch to "questionpreview" window
    And I click on "Preview options" "link"
    And I set the field "How questions behave" to "Immediate feedback"
    And I press "Start again with these options"
    And I click on ".qtype_mtf_row:contains('option text 1') input[value=1]" "css_element"
    And I click on ".qtype_mtf_row:contains('option text 2') input[value=2]" "css_element"
    And I press "Check"
    Then I should see "Mark 1.00 out of 1.00"
    And I press "Start again"
    And I click on ".qtype_mtf_row:contains('option text 1') input[value=1]" "css_element"
    And I click on ".qtype_mtf_row:contains('option text 2') input[value=1]" "css_element"
    And I press "Check"
    Then I should see "Mark 0.50 out of 1.00"
