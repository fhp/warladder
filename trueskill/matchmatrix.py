from trueskill import Rating, TrueSkill, quality, quality_1vs1, rate, rate_1vs1, setup
import json
import sys

jsonString = sys.stdin.read()
try:
	jsonInput = json.loads(jsonString)
except ValueError:
	sys.exit(1)

if "players" not in jsonInput:
	sys.exit(1)

env = TrueSkill(mu=1000.0, sigma=1000.0/3, beta=1000.0/6, tau=1000.0/300, draw_probability=0.1)

players = {}
for player in jsonInput["players"]:
	if "id" not in player or "mu" not in player or "sigma" not in player:
		sys.exit(1)
	players[player["id"]] = env.create_rating(player["mu"], player["sigma"])

qualityList = []
for id1 in players:
	for id2 in players:
		if id2 > id1:
			quality = env.quality([[players[id1]], [players[id2]]])
			qualityList.append({'id1': id1, 'id2': id2, 'quality': quality})

print json.dumps(qualityList)
