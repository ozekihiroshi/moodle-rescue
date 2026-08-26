# Chapter 4 design v39 — Objects and classes

## Placement

Insert this chapter after Chapter 3 without changing Chapters 1–3. Rename the
current Chapter 4 to Chapter 5 and the current Chapter 5 to Chapter 6.

## Chapter purpose

Learners have already represented records with dictionaries and organised work
with functions. This chapter revisits a familiar kind of problem and shows what
changes when related data and operations are placed in an object.

The chapter must not present classes as automatically shorter or universally
better. A few simple records may still be clearer as dictionaries and
functions. Classes become useful when an entity has state, several permitted
operations, and rules that must remain true as the program grows.

## Route

### 4.1 From records and functions to objects

- Compare a dictionary plus functions with a first class.
- Distinguish class, instance, attribute, method, and object identity.
- Define `__init__`, create several independent instances, and use `self`.
- Explain that a class describes how objects are created and behave.

### 4.2 State, methods, and valid objects

- Change state through methods rather than scattered dictionary updates.
- Validate constructor arguments and state-changing operations.
- Use exceptions to reject invalid operations without leaving partial changes.
- Add a useful string representation and test state before and after a method.

### 4.3 Collections, composition, and responsibility

- Store objects in a list or dictionary and search by identifier.
- Build a manager object that contains domain objects.
- Decide which class should own an operation.
- Keep input/output orchestration separate from domain rules.

### 4.4 Persistence and testing class-based programs

- Convert a CSV row into an object and an object back into a record.
- Save and reload without exposing file-format details inside every method.
- Test constructors, normal transitions, rejected transitions, and independent
  instances with assertions.
- Treat class attributes, inheritance, and `dataclasses` as further study, not
  prerequisites for the project.

### 4.5 Applied project — Community equipment lending desk

A learning centre lends shared laptops, projectors, routers, and training kits.
The current collection of dictionaries can represent the items, but updates are
spread across unrelated functions. Learners complete `EquipmentItem` and
`LendingDesk` so that lending and return rules live with the state they protect.

The project requires:

- unique, non-empty item identifiers and names;
- an item that knows whether it is available and who borrowed it;
- rejection of double lending and return of an item that is not on loan;
- a desk that owns a collection of item objects;
- search, add, lend, return, available-item listing, and summary operations;
- CSV export through object-to-record conversion;
- a supplied checker that reports `ALL TESTS PASSED`.

The learner edits one starter file. A reference solution and checker are kept as
separate distribution assets. The brief states the exact class and method
contracts; hidden requirements are not allowed.

## Required versus further study

Required:

- class, instance, constructor, `self`, instance attribute, method;
- validation and exceptions around state transitions;
- collections of objects and composition;
- object/record conversion and assertion-based checks.

Supporting:

- identity versus equal-looking values;
- `__str__` for readable display;
- underscore naming as a convention rather than security.

Further study:

- inheritance and method overriding;
- abstract base classes and protocols;
- class methods, static methods, properties, and descriptors;
- `dataclasses` and type-checking tools.

## Assessment structure

Each of 4.1–4.4 has a lesson page, Python Lab notebook, and ten-question
repeatable knowledge check. Section 4.5 has a project brief, project notebook,
and Moodle assignment. The project checker tests only the published contract.

## Transition

Chapter 4 produces a small program whose state and rules are organised into
cooperating objects. Chapter 5 then returns to analysed data and asks how to
communicate evidence. Chapter 6 applies the established workflow at larger
scale.

