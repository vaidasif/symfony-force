@user
Feature: Signing up
  In order to use application
  As an anonymous user
  I need to be able to signup

  Scenario: signup with valid email
    Given I am on "signup" page
    When I signup as "luke.skywalker@datadog.lt"
    Then I should see success notification "You should receive confirmation email shortly"
    And I should see "Login" on page headline
    And I should receive an email to "luke.skywalker@datadog.lt"

  Scenario: cannot signup with a confirmed email address
    Given confirmed user named "Luke Skywalker"
    And I am on "signup" page
    When I attempt to signup as "luke.skywalker@datadog.lt"
    Then I should see a form field error "Email luke.skywalker@datadog.lt is already confirmed"

  Scenario: signing up with unconfirmed email resends confirmation token
    Given unconfirmed user named "Luke Skywalker"
    And I am on "signup" page
    When I attempt to signup as "luke.skywalker@datadog.lt"
    Then I should see info notification "Confirmation email was successfully resent to luke.skywalker@datadog.lt"

  Scenario: try to signup with invalid email
    Given I am on "signup" page
    When I signup as "luke"
    Then I should see a form field error "Email address is not valid"

