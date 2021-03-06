Feature: get cms blocks

  In order to see cms blocks
  As an api user
  I should be able to get list of cms blocks

  Scenario: api resource is protected
    When I send GET request to "/api/cms-blocks"
    Then the response code should be 401

  Scenario: can list cms blocks
    Given confirmed user named "Chewbacca Wookiee"
    And I'm authenticated as "chewbacca.wookiee@datadog.lt"
    When I send GET request to "/api/cms-blocks"
    Then the response code should be 200
    And the response should contain json:
      """
      {
        "blocks": [
          {
            "id": 1,
            "name": "Footer"
          },
          {
            "id": 2,
            "name": "Css"
          },
          {
            "id": 3,
            "name": "Js"
          }
        ]
      }
      """

  Scenario: can get paged cms blocks
    Given confirmed user named "Chewbacca Wookiee"
    And I'm authenticated as "chewbacca.wookiee@datadog.lt"
    When I send GET request to "/api/cms-blocks?page=2&limit=1"
    Then the response code should be 200
    And the response should match json:
      """
      {
        "blocks": [
          {
            "id": 2,
            "alias": "css",
            "name": "Css",
            "content": "\/* cms block for css *\/",
            "createdAt": "/(.+)/",
            "updatedAt": "/(.+)/"
          }
        ]
      }
      """
