# Contributing to PKKI ITERA

Thank you for your interest in contributing to PKKI ITERA! This document provides guidelines and information for contributors.

## 📋 Table of Contents

- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
- [Coding Standards](#coding-standards)
- [Pull Request Process](#pull-request-process)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Features](#suggesting-features)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Community Guidelines](#community-guidelines)

## Community Guidelines

We are committed to providing a welcoming and inclusive environment for all contributors. Please be respectful, constructive, and professional in all interactions. If you encounter any issues or inappropriate behavior, please report it to [pkki@itera.ac.id](mailto:pkki@itera.ac.id).

## Getting Started

### Prerequisites

Before contributing, ensure you have:

- **PHP** 8.2+
- **Composer** 2.0+
- **Node.js** 18+
- **MySQL** 8.0+ or **PostgreSQL** 13+
- **Git** for version control
- **IDE/Editor** with PHP and JavaScript support

### Development Setup

1. **Fork the repository** on GitHub
2. **Clone your fork** locally:
   ```bash
   git clone https://github.com/your-username/pkki.itera.git
   cd pkki.itera
   ```

3. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

4. **Set up environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database** in `.env` file

6. **Run migrations and seeders**:
   ```bash
   php artisan migrate
   php artisan db:seed
   php artisan shield:generate --all
   ```

7. **Start development servers**:
   ```bash
   php artisan serve
   npm run dev
   ```

## How to Contribute

### Types of Contributions

We welcome several types of contributions:

- 🐛 **Bug fixes**
- ✨ **New features**
- 📚 **Documentation improvements**
- 🧪 **Tests**
- 🔧 **Code refactoring**
- 🌐 **Translations**
- 🎨 **UI/UX improvements**

### Development Workflow

1. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** following our coding standards

3. **Write/update tests** for your changes

4. **Run tests** to ensure everything works:
   ```bash
   php artisan test
   npm run test
   ```

5. **Commit your changes** using conventional commits:
   ```bash
   git commit -m "feat: add new submission workflow feature"
   ```

6. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

7. **Create a Pull Request** on GitHub

## Coding Standards

### PHP Standards

We follow **PSR-12** coding standards and Laravel conventions:

```php
// ✅ Good
class SubmissionController extends Controller
{
    public function store(CreateSubmissionRequest $request): RedirectResponse
    {
        $submission = $this->submissionService->create($request->validated());
        
        return redirect()
            ->route('admin.submissions.show', $submission)
            ->with('success', 'Submission created successfully!');
    }
}

// ❌ Avoid
class submissionController extends Controller 
{
    public function store($request) {
        $data = $request->all();
        $submission = Submission::create($data);
        return redirect('/admin/submissions/'.$submission->id);
    }
}
```

### JavaScript/React Standards

- Use **ES6+** features and modern React patterns
- Follow **React Hooks** conventions
- Use **functional components** over class components
- Implement **proper prop types** or TypeScript

```jsx
// ✅ Good
import React, { useState, useEffect } from 'react';

const SubmissionForm = ({ onSubmit, initialData = {} }) => {
    const [formData, setFormData] = useState(initialData);
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        
        try {
            await onSubmit(formData);
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            {/* Form content */}
        </form>
    );
};

export default SubmissionForm;
```

### Git Commit Standards

We use **Conventional Commits** specification:

```bash
# Format
<type>[optional scope]: <description>

# Types
feat:     # New feature
fix:      # Bug fix
docs:     # Documentation only changes
style:    # Changes that don't affect code meaning
refactor: # Code change that neither fixes bug nor adds feature
test:     # Adding missing tests
chore:    # Changes to build process or auxiliary tools

# Examples
feat(submissions): add document upload functionality
fix(auth): resolve login redirect issue
docs(readme): update installation instructions
test(models): add unit tests for User model
```

## Pull Request Process

### Before Submitting

- ✅ Ensure all tests pass
- ✅ Follow coding standards
- ✅ Update documentation if needed
- ✅ Add/update tests for new features
- ✅ Verify no breaking changes

### PR Requirements

1. **Clear title** following conventional commits
2. **Detailed description** of changes
3. **Screenshots** for UI changes
4. **Test coverage** for new code
5. **Documentation updates** if applicable

## Reporting Bugs

### Bug Report Template

When reporting bugs, please use this template:

```markdown
**Describe the bug**
A clear and concise description of what the bug is.

**To Reproduce**
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected behavior**
A clear description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Environment:**
- OS: [e.g. iOS]
- Browser [e.g. chrome, safari]
- Version [e.g. 22]
- PHP Version:
- Laravel Version:

**Additional context**
Add any other context about the problem here.
```

### Before Reporting

- 🔍 **Search existing issues** to avoid duplicates
- 🧪 **Try to reproduce** the issue
- 📝 **Gather relevant information** (logs, screenshots)
- 🎯 **Use a clear, descriptive title**

## Suggesting Features

### Feature Request Template

```markdown
**Is your feature request related to a problem?**
A clear description of what the problem is.

**Describe the solution you'd like**
A clear description of what you want to happen.

**Describe alternatives you've considered**
Alternative solutions or features you've considered.

**Additional context**
Add any other context or screenshots about the feature request.

**Implementation ideas**
If you have ideas about how to implement this feature.
```

## Security Vulnerabilities

If you discover a security vulnerability, please:

1. **Do NOT** open a public issue
2. **Email** details to [security@itera.ac.id](mailto:security@itera.ac.id)
3. **Include** steps to reproduce
4. **Wait** for confirmation before disclosure

We take security seriously and will respond promptly to legitimate security concerns.

## Recognition

Contributors will be recognized in:
- 📋 **CONTRIBUTORS.md** file
- 🎉 **Release notes** for major contributions
- 💬 **GitHub discussions** and social media
- 📧 **Personal acknowledgment** for significant contributions

## Questions and Support

- 💬 **GitHub Discussions** for general questions
- 🐛 **GitHub Issues** for bugs and features
- 📧 **Email** [pkki@itera.ac.id](mailto:pkki@itera.ac.id) for direct contact
- 📚 **Documentation** at [docs/](docs/) directory

---

Thank you for contributing to PKKI ITERA! Your efforts help make intellectual property management better for Institut Teknologi Sumatera. 🚀

**Happy Coding!** 💻✨
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Pull Request Process](#pull-request-process)
- [Issue Guidelines](#issue-guidelines)
- [Security Vulnerabilities](#security-vulnerabilities)

## Code of Conduct

This project and everyone participating in it is governed by our [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL/MariaDB
- Git

### Development Setup

1. **Fork the repository**
   ```bash
   git clone https://github.com/your-username/pkki.itera.git
   cd pkki.itera
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   # Configure your database in .env
   ```

4. **Run migrations**
   ```bash
   php artisan migrate
   php artisan db:seed
   php artisan shield:generate --all
   ```

5. **Build assets**
   ```bash
   npm run dev
   ```

6. **Start development server**
   ```bash
   php artisan serve
   ```

## How to Contribute

### 🐛 Reporting Bugs

Before creating bug reports, please check the issue list as you might find out that you don't need to create one. When you are creating a bug report, please include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples to demonstrate the steps**
- **Describe the behavior you observed and what behavior you expected**
- **Include screenshots if possible**
- **Include your environment details** (PHP version, Laravel version, etc.)

### 🚀 Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear and descriptive title**
- **Provide a step-by-step description of the suggested enhancement**
- **Provide specific examples to demonstrate the steps**
- **Describe the current behavior and explain the behavior you expected**
- **Explain why this enhancement would be useful**

### 💡 Contributing Code

1. **Check existing issues** - Look for existing issues that match your contribution
2. **Create an issue** - If no issue exists, create one to discuss your changes
3. **Fork and create a branch** - Create a feature branch from `main`
4. **Make your changes** - Follow our coding standards
5. **Test your changes** - Ensure all tests pass
6. **Submit a pull request** - Reference the issue in your PR

## Development Workflow

### Branch Naming Convention

- `feature/description` - For new features
- `bugfix/description` - For bug fixes
- `hotfix/description` - For urgent fixes
- `chore/description` - For maintenance tasks

### Commit Message Convention

We follow the [Conventional Commits](https://conventionalcommits.org/) specification:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

**Types:**
- `feat` - A new feature
- `fix` - A bug fix
- `docs` - Documentation only changes
- `style` - Changes that do not affect the meaning of the code
- `refactor` - A code change that neither fixes a bug nor adds a feature
- `perf` - A code change that improves performance
- `test` - Adding missing tests or correcting existing tests
- `chore` - Changes to the build process or auxiliary tools

**Examples:**
```bash
feat(auth): add Google OAuth integration
fix(validation): resolve email validation bug
docs(api): update authentication documentation
```

## Coding Standards

### PHP Code Standards

- **Follow PSR-12** coding standard
- **Use meaningful variable and function names**
- **Add PHPDoc blocks** for all public methods
- **Use type hints** wherever possible
- **Keep methods small and focused**

```php
/**
 * Create a new submission with validation.
 *
 * @param array<string, mixed> $data
 * @param User $user
 * @return Submission
 * @throws ValidationException
 */
public function createSubmission(array $data, User $user): Submission
{
    // Implementation
}
```

### Frontend Code Standards

- **Use meaningful component and variable names**
- **Follow React best practices**
- **Use TypeScript when possible**
- **Keep components small and reusable**

### Laravel Specific Guidelines

- **Use Eloquent relationships** instead of raw queries
- **Implement proper validation** using Form Requests
- **Use resource classes** for API responses
- **Follow Laravel naming conventions**

```php
// Good
class SubmissionController extends Controller
{
    public function store(CreateSubmissionRequest $request): JsonResponse
    {
        $submission = $this->submissionService->create($request->validated());
        
        return response()->json(new SubmissionResource($submission));
    }
}
```

### Database Guidelines

- **Use descriptive migration names**
- **Always include rollback methods**
- **Use proper foreign key constraints**
- **Add database indexes for performance**

```php
// Migration example
public function up(): void
{
    Schema::create('submissions', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('title');
        $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
        
        $table->index(['status', 'created_at']);
    });
}
```

## Pull Request Process

### Before Submitting

1. **Update documentation** if needed
2. **Add tests** for new functionality
3. **Run the test suite** - `php artisan test`
4. **Run code formatting** - `./vendor/bin/pint`
5. **Update the changelog** if applicable

### PR Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] New tests added for new functionality
- [ ] Manual testing completed

## Screenshots
(if applicable)

## Related Issues
Closes #(issue number)
```

### Review Process

1. **Automated checks** must pass (CI/CD pipeline)
2. **Code review** by at least one maintainer
3. **Testing verification** by reviewers
4. **Documentation review** if applicable
5. **Final approval** before merge

## Issue Guidelines

### Bug Report Template

```markdown
**Bug Description**
A clear and concise description of what the bug is.

**Steps to Reproduce**
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

**Expected Behavior**
A clear description of what you expected to happen.

**Screenshots**
If applicable, add screenshots to help explain your problem.

**Environment:**
- OS: [e.g. iOS]
- Browser [e.g. chrome, safari]
- PHP Version [e.g. 8.2]
- Laravel Version [e.g. 11.9]

**Additional Context**
Add any other context about the problem here.
```

### Feature Request Template

```markdown
**Feature Description**
A clear and concise description of the feature you'd like to see.

**Problem Statement**
Describe the problem this feature would solve.

**Proposed Solution**
Describe the solution you'd like to see implemented.

**Alternatives Considered**
Describe any alternative solutions you've considered.

**Additional Context**
Add any other context or screenshots about the feature request.
```

## Security Vulnerabilities

If you discover a security vulnerability, please send an email to the PKKI ITERA team instead of creating a public issue. Security vulnerabilities will be promptly addressed.

**Contact**: [security@pkki.itera.ac.id](mailto:security@pkki.itera.ac.id)

## Recognition

Contributors will be recognized in our [README.md](README.md) and release notes. We appreciate all contributions, whether they're code, documentation, bug reports, or feature suggestions!

## Questions?

Don't hesitate to contact us:
- **GitHub Discussions**: [Project Discussions](https://github.com/labtekozt/pkki.itera/discussions)
- **Issues**: [GitHub Issues](https://github.com/labtekozt/pkki.itera/issues)
- **Email**: [pkki@itera.ac.id](mailto:pkki@itera.ac.id)

Thank you for contributing to PKKI ITERA! 🚀
