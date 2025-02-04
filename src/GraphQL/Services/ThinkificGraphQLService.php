<?php

namespace WooNinja\ThinkificSaloon\GraphQL\Services;

use Saloon\Contracts\Authenticator;
use WooNinja\ThinkificSaloon\GraphQL\Auth\ThinkificAuthenticator;
use WooNinja\ThinkificSaloon\GraphQL\Connectors\ThinkificConnector;
use WooNinja\ThinkificSaloon\GraphQL\Interfaces\Thinkific;


final class ThinkificGraphQLService implements Thinkific
{
    private string $token;
    private ThinkificConnector|bool $connector = false;

    private Authenticator|bool $authenticator = false;

    public UserService $users;

    public GroupService $groups;

    public CourseService $courses;

    public AssignmentService $assignments;

    public SurveyService $surveys;

    public function __construct(string $token)
    {
        $this->token = $token;

        $this->users = new UserService($this);

        $this->groups = new GroupService($this);

        $this->courses = new CourseService($this);

        $this->assignments = new AssignmentService($this);

        $this->surveys = new SurveyService($this);
    }

    /**
     * @return ThinkificConnector
     */
    public function connector(): ThinkificConnector
    {
        if ($this->connector) {
            return $this->connector;
        }

        /**
         * Default Connector
         */
        return (new ThinkificConnector())
            ->authenticate($this->authenticator());

    }

    /**
     * @return Authenticator
     */
    public function authenticator(): Authenticator
    {
        if ($this->authenticator) {
            return $this->authenticator;
        }

        return new ThinkificAuthenticator(
            $this->token
        );
    }

    /**
     * Dynamically set the Connector
     *
     * @param ThinkificConnector $connector
     * @return void
     */
    public function setConnector(ThinkificConnector $connector): void
    {
        $this->connector = $connector;
    }

    /**
     * Dynamically set the Authenticator
     *
     * @param Authenticator $authenticator
     * @return void
     */
    public function setAuthenticator(Authenticator $authenticator): void
    {
        $this->authenticator = $authenticator;
    }

}