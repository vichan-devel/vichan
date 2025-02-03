name: Bug Report
description: File a bug report for Vichan
title: "[BUG] "
labels: ["bug"]
assignees: []

body:
  - type: markdown
    attributes:
      value: |
        **Thank you for reporting a bug! Please provide as much detail as possible.**
        
        Before submitting, check the [Vichan Wiki](https://vichan.info) to see if there's already a solution to your problem.

  - type: input
    id: bug_description
    attributes:
      label: "Describe the bug"
      description: "A clear and concise description of what the bug is."
      placeholder: "Posting doesn't go through and displays a collation error. The exact error message given is the text below and I've attached a screenshot..."
    validations:
      required: true

  - type: textarea
    id: steps_to_reproduce
    attributes:
      label: "Steps to Reproduce"
      description: "Provide step-by-step instructions to reproduce the issue."
      placeholder: |
        1. Go to '...'
        2. Click on '....'
        3. Scroll down to '....'
        4. See error
      render: markdown
    validations:
      required: true

  - type: textarea
    id: expected_behavior
    attributes:
      label: "Expected Behavior"
      description: "What did you expect to happen?"
      placeholder: "Expected behavior here..."
      render: markdown
    validations:
      required: true

  - type: textarea
    id: server_specs
    attributes:
      label: "Server Specifications"
      description: "Provide details about your server environment. If you're unsure about any of this, you might be using shared hosting (Hostinger, HostGator, Serv00, etc). If so, put the name of your hosting provider here."
      placeholder: |
        - OS: (Ubuntu, CentOS, Windows Server 2025, etc.)
        - PHP Version: (e.g., 7.4, 8.0, 8.1)
        - Web Server: (Apache, NGINX, etc.)
        - Database: (MySQL, MariaDB, etc.)
        - Vichan Version: (5.2.0, 5.3.0 (dev branch), etc)
      render: markdown
    validations:
      required: true

  - type: textarea
    id: additional_context
    attributes:
      label: "Additional Context"
      description: "Any other details we should know?"
      placeholder: "Add any additional context here..."
      render: markdown
