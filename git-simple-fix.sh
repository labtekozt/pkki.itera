#!/bin/bash

# Simple Git Fix - Safe approach to clean up git state
# This is a safer alternative to the complete cleanup

echo "🔧 SIMPLE GIT FIX"
echo "=================="

# Check git status
echo "Current git status:"
git status --porcelain

# 1. Abort any ongoing operations
if [ -d ".git/rebase-merge" ] || [ -d ".git/rebase-apply" ]; then
    echo "Aborting rebase..."
    git rebase --abort
fi

if [ -f ".git/MERGE_HEAD" ]; then
    echo "Aborting merge..."
    git merge --abort
fi

# 2. Clean working directory
echo "Cleaning working directory..."
git clean -fd

# 3. Reset to last clean commit
echo "Resetting to clean state..."
git reset --hard HEAD

# 4. Check current branch
echo "Current branch: $(git branch --show-current)"

# 5. Show recent commits
echo "Recent commits:"
git log --oneline -5

echo ""
echo "✅ Git state cleaned!"
echo ""
echo "Next steps:"
echo "1. Check if your repository is now clean: git status"
echo "2. If you want to remove sensitive data from history: ./git-fix-complete.sh"
echo "3. To push changes: git push origin main"
