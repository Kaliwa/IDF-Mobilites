"use client";

import { useEffect, useReducer } from "react";
import type { ComponentType, SVGProps } from "react";
import { glassTile, iconBadge, linkArrow, sectionAccent } from "../../lib/ui";
import {
  fetchEvents,
  fetchNextStep,
  type OrientationEvent,
  type OrientationQuestion,
  type OrientationRecommendation,
  type OrientationStep,
} from "../../lib/orientation";
import { Notice } from "../auth/Notice";
import {
  ArrowRightIcon,
  CardIcon,
  ClockIcon,
  CompassIcon,
  DocumentIcon,
  LifeBuoyIcon,
  SearchIcon,
} from "../home/icons";
import { QuestionStep } from "./QuestionStep";
import { RecommendationView } from "./RecommendationView";

// Associe le nom d'icône renvoyé par l'API à un composant de la librairie existante.
const ICONS: Record<string, ComponentType<SVGProps<SVGSVGElement>>> = {
  compass: CompassIcon,
  document: DocumentIcon,
  card: CardIcon,
  clock: ClockIcon,
  search: SearchIcon,
  lifebuoy: LifeBuoyIcon,
};

type View = "events" | "question" | "result";

type State = {
  view: View;
  loading: boolean;
  error: string | null;
  events: OrientationEvent[];
  scenario: string | null;
  /** Pile des questions visitées ; la dernière est la question courante. */
  questions: OrientationQuestion[];
  recommendation: OrientationRecommendation | null;
};

type Action =
  | { type: "loading" }
  | { type: "events_loaded"; events: OrientationEvent[] }
  | { type: "scenario_selected"; scenario: string }
  | { type: "question_loaded"; question: OrientationQuestion }
  | { type: "recommendation_loaded"; recommendation: OrientationRecommendation }
  | { type: "back" }
  | { type: "reset" }
  | { type: "error"; message: string };

const initialState: State = {
  view: "events",
  loading: true,
  error: null,
  events: [],
  scenario: null,
  questions: [],
  recommendation: null,
};

function reducer(state: State, action: Action): State {
  switch (action.type) {
    case "loading":
      return { ...state, loading: true, error: null };
    case "events_loaded":
      return { ...state, events: action.events, loading: false, view: "events" };
    case "scenario_selected":
      return { ...state, scenario: action.scenario, questions: [], recommendation: null };
    case "question_loaded":
      return {
        ...state,
        questions: [...state.questions, action.question],
        recommendation: null,
        view: "question",
        loading: false,
      };
    case "recommendation_loaded":
      return {
        ...state,
        recommendation: action.recommendation,
        view: "result",
        loading: false,
      };
    case "back": {
      // Depuis la recommandation, on revient simplement à la dernière question.
      if (state.recommendation) {
        return { ...state, recommendation: null, view: "question", error: null };
      }
      const questions = state.questions.slice(0, -1);
      if (questions.length === 0) {
        return { ...state, questions: [], scenario: null, view: "events", error: null };
      }
      return { ...state, questions, view: "question", error: null };
    }
    case "reset":
      return {
        ...state,
        scenario: null,
        questions: [],
        recommendation: null,
        view: "events",
        loading: false,
        error: null,
      };
    case "error":
      return { ...state, loading: false, error: action.message };
    default:
      return state;
  }
}

export function OrientationWizard() {
  const [state, dispatch] = useReducer(reducer, initialState);

  useEffect(() => {
    let active = true;
    fetchEvents()
      .then((events) => {
        if (active) {
          dispatch({ type: "events_loaded", events });
        }
      })
      .catch((error: unknown) => {
        if (active) {
          dispatch({ type: "error", message: messageFrom(error) });
        }
      });
    return () => {
      active = false;
    };
  }, []);

  function applyStep(step: OrientationStep) {
    if (step.type === "question") {
      dispatch({ type: "question_loaded", question: step.question });
    } else {
      dispatch({ type: "recommendation_loaded", recommendation: step.recommendation });
    }
  }

  async function selectEvent(scenario: string) {
    dispatch({ type: "loading" });
    dispatch({ type: "scenario_selected", scenario });
    try {
      applyStep(await fetchNextStep({ scenario }));
    } catch (error: unknown) {
      dispatch({ type: "error", message: messageFrom(error) });
    }
  }

  async function submitAnswers(answers: string[]) {
    const current = state.questions[state.questions.length - 1];
    if (!current || !state.scenario) {
      return;
    }
    dispatch({ type: "loading" });
    try {
      applyStep(
        await fetchNextStep({
          scenario: state.scenario,
          currentQuestion: current.code,
          answers,
        }),
      );
    } catch (error: unknown) {
      dispatch({ type: "error", message: messageFrom(error) });
    }
  }

  const currentQuestion = state.questions[state.questions.length - 1] ?? null;

  return (
    <section className="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
      {state.error ? (
        <div className="mb-6">
          <Notice tone="error">{state.error}</Notice>
        </div>
      ) : null}

      {/* 1. Choix de l'événement de vie */}
      {state.view === "events" ? (
        <div className="rise-in">
          <span className={sectionAccent} aria-hidden="true" />
          <h1 className="text-3xl font-bold tracking-tight text-anthracite sm:text-4xl">
            Quel est votre moment de vie&nbsp;?
          </h1>
          <p className="mt-3 max-w-xl text-muted">
            Choisissez la situation qui vous correspond : on vous oriente vers l&apos;offre
            et les aides les plus adaptées en quelques questions.
          </p>

          {state.loading && state.events.length === 0 ? (
            <div className="mt-8 grid gap-4 sm:grid-cols-2" aria-hidden="true">
              {[0, 1, 2, 3].map((index) => (
                <span
                  key={index}
                  className="h-28 animate-pulse rounded-2xl bg-white/45"
                />
              ))}
            </div>
          ) : (
            <ul className="mt-8 grid gap-4 sm:grid-cols-2">
              {state.events.map((event) => {
                const Icon = ICONS[event.icone ?? ""] ?? CompassIcon;
                return (
                  <li key={event.code}>
                    <button
                      type="button"
                      onClick={() => void selectEvent(event.code)}
                      disabled={state.loading}
                      className={`${glassTile} group flex h-full w-full flex-col gap-3 p-5 text-left disabled:cursor-not-allowed disabled:opacity-60`}
                    >
                      <span className={iconBadge}>
                        <Icon width={22} height={22} />
                      </span>
                      <span className="text-lg font-semibold text-anthracite">
                        {event.label}
                      </span>
                      {event.description ? (
                        <span className="text-sm leading-relaxed text-muted">
                          {event.description}
                        </span>
                      ) : null}
                      <span className={`${linkArrow} mt-auto pt-2 text-sm`}>
                        Commencer
                        <ArrowRightIcon
                          width={15}
                          height={15}
                          className="transition-transform group-hover:translate-x-0.5"
                        />
                      </span>
                    </button>
                  </li>
                );
              })}
            </ul>
          )}
        </div>
      ) : null}

      {/* 2. Questions pas-à-pas */}
      {state.view === "question" && currentQuestion ? (
        <QuestionStep
          key={currentQuestion.code}
          question={currentQuestion}
          onSubmit={(answers) => void submitAnswers(answers)}
          onBack={() => dispatch({ type: "back" })}
          loading={state.loading}
        />
      ) : null}

      {/* 3. Recommandation finale */}
      {state.view === "result" && state.recommendation ? (
        <RecommendationView
          recommendation={state.recommendation}
          onBack={() => dispatch({ type: "back" })}
          onRestart={() => dispatch({ type: "reset" })}
        />
      ) : null}
    </section>
  );
}

function messageFrom(error: unknown): string {
  return error instanceof Error ? error.message : "Une erreur est survenue. Réessayez.";
}
