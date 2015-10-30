@core @core_grades @gradereport_user
Feature: We can use the user report
  As a user
  I browse to the User report

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |

    Scenario: Verify we can view a user grade report with no users enrolled.
      Given I log in as "admin"
      And I am on site homepage
      And I follow "Course 1"
      And I navigate to "Grades" node in "Course administration"
      And I select "User report" from the "Grade report" singleselect
      And I press "Go"
      And I select "All users (0)" from the "Select all or one user" singleselect
      And I click on "Go" "button" in the "#choosegradeuser" "css_element"
      Then I should see "No students enrolled in this course yet"

   Scenario: Verify that invalid weights do not appear for dropped grades
     Given the following "users" exist:
        | username | firstname | lastname | email | idnumber |
        | student1 | Student | 1 | student1@emample.com | s1 |
     And the following "course enrolments" exist:
       | user | course | role |
       | student1 | C1 | student |
     And the following "grade categories" exist:
       | fullname | course |
       | Category1 | C1 |
     And the following "grade items" exist:
       | itemname | course | category |
       | Grade ME | C1 | Category1 |
     And I log in as "admin"
     And I am on site homepage
     And I follow "Course 1"
     And I navigate to "Grades" node in "Course administration"
     And I select "Grader report" from the "Grade report" singleselect
     And I turn editing mode on
     And I set the field "Student 1 Grade ME grade" to "90"
     And I click on "Save changes" "button"
     And I turn editing mode off
     And I select "User report" from the "Grade report" singleselect
     And I select "Student 1" from the "Select all or one user" singleselect
     Then "100.00 %" "text" should exist in the "Category1 total" "table_row"
     And "90.00" "text" should exist in the "Category1 total" "table_row"
     And I select "Grader report" from the "Grade report" singleselect
     And I turn editing mode on
     And I set the field "Student 1 Grade ME grade" to ""
     And I click on "Save changes" "button"
     And I turn editing mode off
     And I select "User report" from the "Grade report" singleselect
     And I select "Student 1" from the "Select all or one user" singleselect
     Then "100.00 %" "text" should not exist in the "Category1 total" "table_row"
