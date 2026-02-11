@report @report_lmsace_reports @report_lmsace_reports_management

Feature: Setup different configurations to customize the reports

  In order to use the reports
  As admin
  I need to be able to configure the lmsace reports plugin

  @javascript
  Scenario Outline: Enable or disable the reports to show or hide from users
    Given I log in as "admin"
    And I navigate to "Plugins > Reports > LMSACE reports" in site administration
    And I should see "<report>" in the "#admin-visiblesitereports" "css_element"
    And I click on "#id_s_reports_lmsace_reports_visiblesitereports_<configname>" "css_element"
    And I press "Save changes"
    And I navigate to "Reports > LMSACE reports" in site administration
    And I wait until the page is ready
    And "<reportelement>" "css_element" should not exist in the "#site-report" "css_element"
    And I navigate to "Plugins > Reports > LMSACE reports" in site administration
    And I should see "<report>" in the "#admin-visiblesitereports" "css_element"
    And I click on "#id_s_reports_lmsace_reports_visiblesitereports_<configname>" "css_element"
    And I press "Save changes"
    And I navigate to "Reports > LMSACE reports" in site administration
    And "<reportelement>" "css_element" should exist in the "#site-report" "css_element"

    Examples:
      | report                          | reportelement                      | configname           |
      | Site mini stats widget          | .lmsace-reports.count-block        | stacksitereports     |
      | Site activities overview        | .site-overall-reports.block-report | overallsiteinfo      |
      | Site basic informations         | .site-state-reports.block-report   | sitestateinfo        |
      | Users registration status       | .user-info-block.block-report      | siteusers            |
      | Enrolment methods usage report  | .enroll-method-users.block-report  | enrolmethodusers     |
      | Cohorts informations            | .cohorts-info.block-report         | cohortsinfo          |
      | Top 10 courses by enrolment     | .topcourse-enrollment-reports      | topcourseenrolment   |
      | Top 10 courses by completion    | .topcourse-coursecompletion-reports| topcoursecompletion  |
      | Site users visits               | .site-visits-block.block-report    | sitevisits           |

  @javascript
  Scenario Outline: Enable or disable the visibility of course reports
    Given I log in as "admin"
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And I navigate to "Plugins > Reports > LMSACE reports" in site administration
    And I should see "<report>" in the "#admin-visiblecoursereports" "css_element"
    And I click on "#id_s_reports_lmsace_reports_visiblecoursereports_<configname>" "css_element"
    And I press "Save changes"
    And I navigate to "Reports > LMSACE reports" in site administration
    And I wait until the page is ready
    And "<reportelement>" "css_element" should not exist in the "#course-report" "css_element"
    And I navigate to "Plugins > Reports > LMSACE reports" in site administration
    And I should see "<report>" in the "#admin-visiblecoursereports" "css_element"
    And I click on "#id_s_reports_lmsace_reports_visiblecoursereports_<configname>" "css_element"
    And I press "Save changes"
    And I navigate to "Reports > LMSACE reports" in site administration
    And "<reportelement>" "css_element" should exist in the "#course-report" "css_element"

    Examples:
      | report                              | reportelement               | configname                |
      | Course mini states widget           | .lmsace-reports.count-block | stackcoursereports        |
      | Course modules grades               | .course-modulegrades-block  | coursemodulegrades        |
      | Active and inactive users by month  | .active-inactive-users-block| courseactiveinactiveusers |
      | Course users activity               | .course-status-block        | courseresources           |
      | Course visits by users              | .course-visits-block        | coursevisits              |
      | High scores in course               | .high-score-course-block    | coursehighscore           |

  @javascript
  Scenario Outline: Enable or disable the visibility of user reports
    Given I log in as "admin"
    And the following "users" exist:
      | username |
      | student1 |
    And I navigate to "Plugins > Reports > LMSACE reports" in site administration
    And I should see "<report>" in the "#admin-visibleuserreports" "css_element"
    And I click on "#id_s_reports_lmsace_reports_visibleuserreports_<configname>" "css_element"
    And I press "Save changes"
    And I navigate to "Reports > LMSACE reports" in site administration
    And I wait until the page is ready
    And "<reportelement>" "css_element" should not exist in the "#user-report" "css_element"
    And I navigate to "Plugins > Reports > LMSACE reports" in site administration
    And I should see "<report>" in the "#admin-visibleuserreports" "css_element"
    And I click on "#id_s_reports_lmsace_reports_visibleuserreports_<configname>" "css_element"
    And I press "Save changes"
    And I navigate to "Reports > LMSACE reports" in site administration
    And "<reportelement>" "css_element" should exist in the "#user-report" "css_element"

    Examples:
      | report                  | reportelement               | configname        |
      | User mini stats         | .lmsace-reports.count-block | stackuserreports  |
      | My activities           | .user-myactivities-block    | usermyactivities  |
      | My quizzes              | .user-myquizzes-block       | usermyquizzes     |
      | My assignments          | .user-myassignments-block   | usermyassignments |
      | User logins             | .user-login-block           | userlogins        |
      | User scores             | .user-score-block           | userscore         |
      | Cohorts and groups      | .user-cohort-group-block    | usergroupcohorts  |
      | 10 most visited courses | .user-visits-course-block   | mostvisitcourse   |
