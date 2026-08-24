'use client';

import { useMemo, useState } from 'react';

const plans = [
  { name: 'Free', note: 'For small schools getting started', price: '₱0', unit: 'forever', features: ['100 students', '10 teachers & staff', '1 administrator', 'Moonlight subdomain'], cta: 'Start for free' },
  { name: 'Starter', note: 'For growing school communities', price: '₱5,000', unit: 'per month', features: ['Up to 500 active users', '5 admin-only accounts', 'Custom school domain', 'Full reports & exports'], cta: 'Choose Starter', featured: true },
  { name: 'Growth', note: 'For established institutions', price: '₱8', unit: 'per additional user', features: ['Users 501–1,000', 'Graduated lower rate', 'Priority support', 'Automated backups'], cta: 'Talk to sales' },
  { name: 'Scale', note: 'For large campuses and groups', price: '₱6.50', unit: 'per additional user', features: ['Users 1,001–2,000', 'Best published rate', 'Guided data migration', 'Launch support'], cta: 'Talk to sales' },
];

const benefits = [
  ['01', 'One school, one calm workspace', 'Enrollment, people, classes, and records stay organized in one secure place—without the spreadsheet shuffle.'],
  ['02', 'Ready before the first bell', 'Create your school, import your records, and invite your team through a guided setup that does the heavy lifting.'],
  ['03', 'Pricing that grows fairly', 'Start free, then unlock lower per-user rates as your community grows. No confusing feature maze.'],
];

function peso(value: number) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(value);
}

export default function Home() {
  const [users, setUsers] = useState(750);
  const estimate = useMemo(() => {
    if (users <= 110) return { value: '₱0', note: 'You may qualify for the Free plan.' };
    if (users <= 500) return { value: '₱5,000', note: 'Starter includes up to 500 active users.' };
    if (users <= 1000) return { value: peso(5000 + (users - 500) * 8), note: 'Users above 500 receive the Growth rate.' };
    if (users <= 2000) return { value: peso(9000 + (users - 1000) * 6.5), note: 'Users above 1,000 receive the Scale rate.' };
    return { value: 'Custom', note: 'Let’s build a plan for your institution.' };
  }, [users]);

  return (
    <main>
      <nav className="nav shell" aria-label="Main navigation">
        <a className="brand" href="#top"><span className="moon" aria-hidden="true" />SMS <b>Moonlight</b></a>
        <div className="navlinks"><a href="#features">Why Moonlight</a><a href="#pricing">Pricing</a><a href="#setup">How it works</a></div>
        <a className="btn dark small" href="#contact">Book a demo <span>↗</span></a>
      </nav>

      <section className="hero shell" id="top">
        <div className="heroCopy">
          <span className="pill">✦ SCHOOL MANAGEMENT, BEAUTIFULLY SIMPLIFIED</span>
          <h1>More time for<br /><em>what matters.</em></h1>
          <p>SMS Moonlight brings your students, staff, and school operations together—so your team can spend less time managing systems and more time shaping futures.</p>
          <div className="actions"><a className="btn orange" href="#pricing">Explore plans <span>→</span></a><a className="watch" href="#setup"><i>▶</i> See how it works</a></div>
          <div className="proof"><div><span>JM</span><span>AL</span><span>RS</span></div><p><b>Built with schools, for schools.</b><br />Simple from day one.</p></div>
        </div>

        <div className="preview">
          <div className="ring one" /><div className="ring two" />
          <div className="app">
            <header><a className="brand mini"><span className="moon" />Moonlight</a><div className="search">⌕ &nbsp; Search anything...</div><span className="user">MS</span></header>
            <div className="appBody">
              <aside><b>⌂ &nbsp; Overview</b><span>♙ &nbsp; Students</span><span>◇ &nbsp; Staff</span><span>▦ &nbsp; Classes</span><span>◷ &nbsp; Attendance</span><div><strong>Need help?</strong><small>We’re here for you.</small><button>Contact us</button></div></aside>
              <section className="dash">
                <div className="hello"><div><small>MONDAY, AUGUST 24</small><h3>Good morning, Maria!</h3><p>Here’s what’s happening at your school today.</p></div><button>+ Add student</button></div>
                <div className="stats"><div><i className="mint">♙</i><span><small>Total students</small><strong>486</strong><em>↑ 12 this month</em></span></div><div><i className="peach">◇</i><span><small>Teachers & staff</small><strong>38</strong><em>96% active</em></span></div><div><i className="blue">✓</i><span><small>Today’s attendance</small><strong>94.8%</strong><em>↑ 2.1% this week</em></span></div></div>
                <div className="lower"><div className="chart"><b>Weekly attendance</b><div className="bars">{[58,74,66,91,83].map((h, i) => <i key={i} style={{height: `${h}%`}} />)}</div><small><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span></small></div><div className="activity"><b>Recent activity</b><p><i>✓</i><span><strong>Enrollment approved</strong><small>Grade 8 • 4 mins ago</small></span></p><p><i>↗</i><span><strong>Report exported</strong><small>Attendance • 1 hour ago</small></span></p><p><i>+</i><span><strong>New staff added</strong><small>Faculty • Yesterday</small></span></p></div></div>
              </section>
            </div>
          </div>
          <div className="float setup"><i>✓</i><span><b>Setup complete</b><small>Your school is ready!</small></span></div>
          <div className="float attend"><i>94%</i><span><b>Great attendance</b><small>Across all year levels</small></span></div>
        </div>
      </section>

      <section className="trust"><div className="shell"><p>Everything your school needs.<br /><b>Nothing it doesn’t.</b></p><div><span>✓ Guided setup</span><span>✓ Secure by design</span><span>✓ Local support</span><span>✓ No surprise fees</span></div></div></section>

      <section className="section shell" id="features">
        <div className="sectionHead split"><div><span>WHY MOONLIGHT</span><h2>Complex work.<br /><em>Simple experience.</em></h2></div><p>Thoughtfully designed for the people who keep schools running—from the registrar’s desk to the classroom.</p></div>
        <div className="benefits">{benefits.map(([n, title, copy]) => <article key={n}><small>{n}</small><div className={`art a${n}`}><i /><b /><span /></div><h3>{title}</h3><p>{copy}</p></article>)}</div>
      </section>

      <section className="pricing" id="pricing"><div className="shell">
        <div className="sectionHead center"><span>SIMPLE, TRANSPARENT PRICING</span><h2>Start small. <em>Grow confidently.</em></h2><p>Every plan includes the essentials. Pay less per user as your school community grows.</p></div>
        <div className="plans">{plans.map(plan => <article className={plan.featured ? 'featured' : ''} key={plan.name}>{plan.featured && <div className="popular">MOST POPULAR</div>}<h3>{plan.name}</h3><p>{plan.note}</p><strong>{plan.price}</strong><small>{plan.unit}</small><hr /><ul>{plan.features.map(item => <li key={item}><i>✓</i>{item}</li>)}</ul><a className={`btn ${plan.featured ? 'orange' : 'outline'}`} href="#contact">{plan.cta} <span>→</span></a></article>)}</div>
        <div className="enterprise"><div><span>2,000+ USERS</span><h3>Running a larger institution?</h3><p>Let’s create a plan around your campuses, migration needs, and support requirements.</p></div><a className="btn white" href="#contact">Build a custom plan →</a></div>
        <div className="estimator"><div><span>QUICK ESTIMATE</span><h3>See what Moonlight could cost your school.</h3><p>Move the slider to estimate graduated monthly pricing. Your final quote will reflect active users and onboarding needs.</p></div><div className="tool"><label>Active users <output>{users.toLocaleString()}</output></label><input aria-label="Active users" type="range" min="100" max="2500" step="10" value={users} onChange={e => setUsers(Number(e.target.value))} style={{'--progress': `${((users - 100) / 2400) * 100}%`} as React.CSSProperties} /><div className="labels"><span>100</span><span>500</span><span>1,000</span><span>2,000+</span></div><div className="result"><span><small>ESTIMATED MONTHLY</small><b>{estimate.value}</b></span><p>{estimate.note}</p></div></div></div>
      </div></section>

      <section className="section shell setupSteps" id="setup"><div className="sectionHead center"><span>FROM SIGN-UP TO SCHOOL-READY</span><h2>Up and running in <em>three steps.</em></h2></div><div className="steps"><article><b>1</b><small>CREATE</small><h3>Tell us about your school</h3><p>Choose your plan and add the basics. Your secure workspace is created automatically.</p></article><article><b>2</b><small>SET UP</small><h3>Bring your school records</h3><p>Use guided imports for students and staff, then configure your school year and classes.</p></article><article><b>3</b><small>GO LIVE</small><h3>Invite your team</h3><p>Review your setup, invite administrators and staff, and begin your Moonlight journey.</p></article></div><div className="help">✦ <p><b>Prefer a helping hand?</b> Assisted migration, configuration, and launch support are available through a one-time implementation package.</p></div></section>

      <section className="cta" id="contact"><div className="ctaRing r1" /><div className="ctaRing r2" /><div className="shell"><span className="bigMoon"><i /></span><small>YOUR SCHOOL, IN A BETTER RHYTHM</small><h2>Ready to make school<br />management feel <em>lighter?</em></h2><p>See how SMS Moonlight can fit your school today—and grow with you tomorrow.</p><div><a className="btn orange" href="mailto:hello@smsmoonlight.com?subject=SMS%20Moonlight%20Demo">Book your free demo →</a><a className="btn ghost" href="#pricing">View pricing</a></div></div></section>
      <footer><div className="shell"><a className="brand" href="#top"><span className="moon" />SMS <b>Moonlight</b></a><p>School management, beautifully simplified.</p><nav><a href="#features">Features</a><a href="#pricing">Pricing</a><a href="mailto:hello@smsmoonlight.com">Contact</a></nav><small>© 2026 SMS Moonlight.</small></div></footer>
    </main>
  );
}
