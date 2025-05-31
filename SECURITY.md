# Security Policy

## Supported Versions

We release patches for security vulnerabilities in the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |

## Reporting a Vulnerability

The PKKI ITERA team takes security bugs seriously. We appreciate your efforts to responsibly disclose your findings, and will make every effort to acknowledge your contributions.

### How to Report Security Issues

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them via email to: **pkki-security@itera.ac.id**

You should receive a response within 48 hours. If for some reason you do not, please follow up via email to ensure we received your original message.

### What to Include

Please include the following information in your report:
- Type of issue (e.g. buffer overflow, SQL injection, cross-site scripting, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit the issue

### Preferred Languages

We prefer all communications to be in English or Indonesian.

### Security Response Process

1. **Acknowledgment**: We will acknowledge receipt of your vulnerability report within 48 hours.
2. **Investigation**: Our security team will investigate and validate the vulnerability.
3. **Response**: We will respond with our evaluation of the vulnerability and expected timeline for a fix.
4. **Resolution**: We will work on a fix and coordinate release timing with you.
5. **Disclosure**: After the fix is released, we will publicly acknowledge your responsible disclosure (unless you prefer to remain anonymous).

### Security Measures in PKKI ITERA

Our application implements several security measures:

- **Authentication**: Multi-factor authentication support
- **Authorization**: Role-based access control (RBAC) with Spatie Permission
- **Data Protection**: Encryption of sensitive data at rest and in transit
- **Input Validation**: Comprehensive input validation and sanitization
- **SQL Injection Protection**: Parameterized queries and ORM usage
- **CSRF Protection**: Cross-Site Request Forgery protection on all forms
- **XSS Protection**: Output encoding and Content Security Policy
- **File Upload Security**: Strict file type validation and secure storage
- **Session Security**: Secure session configuration
- **Rate Limiting**: API and form submission rate limiting
- **Audit Logging**: Comprehensive audit trail for all actions

### Security Configuration

Please ensure proper configuration of:
- Environment variables (never commit `.env` files)
- Database credentials
- API keys and secrets
- File permissions
- SSL/TLS certificates
- Firewall rules

## Responsible Disclosure

We ask that you:
- Give us reasonable time to fix the issue before public disclosure
- Avoid accessing or modifying user data
- Don't perform actions that could harm the service or its users
- Don't access data that doesn't belong to you

## Recognition

We believe in recognizing security researchers who help us keep PKKI ITERA secure. Depending on the severity and impact of the vulnerability, we may:
- Publicly acknowledge your contribution (with your permission)
- Include your name in our security acknowledgments
- Provide a letter of recommendation for your responsible disclosure

Thank you for helping keep PKKI ITERA and our users safe!
